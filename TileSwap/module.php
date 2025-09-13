<?php

declare(strict_types=1);

class TileSwap extends IPSModule
{
    public function Create()
    {
        // Never delete this line!
        parent::Create();

        // Properties
        $this->RegisterPropertyString('Targets', '[]'); // [{"ObjectID":12345}, ...]
        $this->RegisterPropertyBoolean('AutoReset', false);
        $this->RegisterPropertyInteger('ResetSeconds', 0);

        // Attributes
        $this->RegisterAttributeInteger('OriginalTarget', 0); // 0 = none stored
        $this->RegisterAttributeInteger('ManagedLinkID', 0);

        // Variables
        $this->RegisterVariableInteger('TileSwitch', $this->Translate('Tile Switch'), '', 1);
        $this->EnableAction('TileSwitch');
        $this->HideTileSwitch();
        $this->ApplyPresentation();

        // Timer
        $this->RegisterTimer('ResetTimer', 0, 'TSWAP_ResetTarget($_IPS[\'TARGET\']);');
    }

    private function HideTileSwitch(): void
    {
        if (!function_exists('IPS_SetHidden')) {
            return;
        }
        $varID = @$this->GetIDForIdent('TileSwitch');
        if ($varID) {
            @IPS_SetHidden($varID, true);
        }
    }

    private function GetOrCreateManagedLinkID(): int
    {
        $linkID = $this->ReadAttributeInteger('ManagedLinkID');
        if ($linkID > 0 && IPS_ObjectExists($linkID)) {
            $obj = IPS_GetObject($linkID);
            if (($obj['ObjectType'] ?? -1) !== 6) { // Not a link
                $linkID = 0;
            } else {
                // Ensure parent is this instance
                if (IPS_GetParent($linkID) !== $this->InstanceID) {
                    @IPS_SetParent($linkID, $this->InstanceID);
                }
                return $linkID;
            }
        }

        // Create new link
        $linkID = @IPS_CreateLink();
        if ($linkID === false || $linkID === 0) {
            $this->SendDebug(__FUNCTION__, 'Failed to create link', 0);
            return 0;
        }
        @IPS_SetParent($linkID, $this->InstanceID);
        @IPS_SetName($linkID, $this->Translate('Tile Link'));
        @IPS_SetIdent($linkID, 'TSWAP_LINK');

        // Set default target to first valid target (if any)
        foreach ($this->GetTargets() as $row) {
            $tid = (int)($row['ObjectID'] ?? 0);
            if ($tid > 0 && IPS_ObjectExists($tid)) {
                @IPS_SetLinkTargetID($linkID, $tid);
                break;
            }
        }

        $this->WriteAttributeInteger('ManagedLinkID', $linkID);
        return $linkID;
    }

    public function Destroy()
    {
        // Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        // Never delete this line!
        parent::ApplyChanges();

        // Maintain variable and set Presentation afterwards (for compatibility with environments where array-parameter is not supported)
        $this->MaintainVariable('TileSwitch', $this->Translate('Tile Switch'), VARIABLETYPE_INTEGER, '', 1, true);
        $this->EnableAction('TileSwitch');
        $this->HideTileSwitch();
        $this->ApplyPresentation();

        // Ensure the managed link exists under this instance
        $linkID = $this->GetOrCreateManagedLinkID();

        // Timer config: disable when no link selected or auto-reset disabled/zero seconds
        $auto = $this->ReadPropertyBoolean('AutoReset');
        $seconds = max(0, $this->ReadPropertyInteger('ResetSeconds'));
        if ($linkID === 0 || !$auto || $seconds === 0) {
            $this->SetTimerInterval('ResetTimer', 0);
            $this->WriteAttributeInteger('OriginalTarget', 0);
        }
    }

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'TileSwitch':
                $this->HandleSwitch((int)$Value);
                break;
            default:
                throw new Exception('Invalid ident: ' . $Ident);
        }
    }

    // Called by timer
    public function ResetTarget(): void
    {
        $linkID = $this->GetOrCreateManagedLinkID();

        // Determine first valid target from list
        $firstTargetID = 0;
        foreach ($this->GetTargets() as $row) {
            $tid = (int)($row['ObjectID'] ?? 0);
            if ($tid > 0 && IPS_ObjectExists($tid)) {
                $firstTargetID = $tid;
                break;
            }
        }

        if ($linkID > 0 && $firstTargetID > 0 && IPS_ObjectExists($linkID)) {
            $obj = IPS_GetObject($linkID);
            if (($obj['ObjectType'] ?? -1) === 6) { // 6 = Link
                IPS_SetLinkTargetID($linkID, $firstTargetID);
                $this->SendDebug(__FUNCTION__, sprintf('Reset link #%d target to first list entry #%d', $linkID, $firstTargetID), 0);
            }
        }

        // Reflect index 1 on the variable if it exists and we have a valid first target
        if ($firstTargetID > 0) {
            $varID = @$this->GetIDForIdent('TileSwitch');
            if ($varID) {
                @SetValueInteger($varID, 1);
            }
        }

        // Clear state and stop timer
        $this->WriteAttributeInteger('OriginalTarget', 0);
        $this->SetTimerInterval('ResetTimer', 0);
    }

    private function HandleSwitch(int $index): void
    {
        $targets = $this->GetTargets();
        $count = count($targets);
        if ($index < 1 || $index > $count) {
            $this->SendDebug(__FUNCTION__, sprintf('Invalid index %d (allowed 1..%d)', $index, $count), 0);
            return;
        }

        $linkID = $this->GetOrCreateManagedLinkID();
        if ($linkID <= 0 || !IPS_ObjectExists($linkID)) {
            $this->SendDebug(__FUNCTION__, 'No valid managed link', 0);
            return;
        }

        $obj = IPS_GetObject($linkID);
        if (($obj['ObjectType'] ?? -1) !== 6) { // 6 = Link
            $this->SendDebug(__FUNCTION__, sprintf('Object #%d is not a link', $linkID), 0);
            return;
        }

        $targetRow = $targets[$index - 1];
        $targetID = (int)($targetRow['ObjectID'] ?? 0);
        if ($targetID <= 0 || !IPS_ObjectExists($targetID)) {
            $this->SendDebug(__FUNCTION__, sprintf('Target at index %d is invalid (ID=%d)', $index, $targetID), 0);
            return;
        }

        // Timer configuration
        $auto = $this->ReadPropertyBoolean('AutoReset');
        $seconds = max(0, $this->ReadPropertyInteger('ResetSeconds'));

        IPS_SetLinkTargetID($linkID, $targetID);
        $this->SendDebug(__FUNCTION__, sprintf('Changed link #%d target to #%d (index %d)', $linkID, $targetID, $index), 0);

        // Reflect the chosen index on the variable
        $varID = $this->GetIDForIdent('TileSwitch');
        if ($varID > 0) {
            SetValueInteger($varID, $index);
        }

        // Start auto-reset timer if enabled
        if ($auto && $seconds > 0) {
            $this->SetTimerInterval('ResetTimer', $seconds * 1000);
        } else {
            $this->SetTimerInterval('ResetTimer', 0);
            $this->WriteAttributeInteger('OriginalTarget', 0);
        }
    }

    private function BuildPresentationConfig(): array
    {
        $options = [];
        $i = 1;
        foreach ($this->GetTargets() as $row) {
            $id = (int)($row['ObjectID'] ?? 0);
            $caption = (string)$id;
            if ($id > 0 && IPS_ObjectExists($id)) {
                $name = @IPS_GetName($id);
                if (is_string($name) && $name !== '') {
                    $caption = $name;
                }
            }
            $options[] = [
                'Caption'    => $caption,
                'Color'      => -1,
                'IconActive' => false,
                'IconValue'  => '',
                'Value'      => $i
            ];
            $i++;
        }

        $ENUM_GUID = '{52D9E126-D7D2-2CBB-5E62-4CF7BA7C5D82}';
        return [
            'ICON'         => '',
            'PRESENTATION' => $ENUM_GUID,
            'LAYOUT'       => 0,
            'OPTIONS'      => json_encode($options, JSON_UNESCAPED_UNICODE)
        ];
    }

    private function ApplyPresentation(): void
    {
        // Assign presentation via dedicated function if available
        if (!function_exists('IPS_SetVariableCustomPresentation')) {
            $this->SendDebug(__FUNCTION__, 'IPS_SetVariableCustomPresentation() not available. Skipping presentation assignment.', 0);
            return;
        }
        $varID = $this->GetIDForIdent('TileSwitch');
        if ($varID > 0) {
            // Ensure no legacy profile interferes
            if (function_exists('IPS_SetVariableCustomProfile')) {
                @IPS_SetVariableCustomProfile($varID, '');
            }
            $presentation = $this->BuildPresentationConfig();
            $ok = @IPS_SetVariableCustomPresentation($varID, $presentation);
            if (!$ok) {
                // Fallback: pass JSON string
                $json = json_encode($presentation, JSON_UNESCAPED_UNICODE);
                $this->SendDebug(__FUNCTION__, 'Array assignment failed, trying JSON string...', 0);
                $ok = @IPS_SetVariableCustomPresentation($varID, $json);
            }
            // Debug what we tried to set
            $this->SendDebug(__FUNCTION__, 'Applied presentation ok=' . ($ok ? 'true' : 'false') . ' data=' . json_encode($presentation, JSON_UNESCAPED_UNICODE), 0);
        }
    }

    /**
     * @return array<int, array{ObjectID:int}>
     */
    private function GetTargets(): array
    {
        $json = $this->ReadPropertyString('Targets');
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return [];
        }
        // Normalize entries and preserve the array order (drag & drop changeOrder writes the new order)
        $out = [];
        foreach ($data as $row) {
            if (is_array($row)) {
                $out[] = [
                    'ObjectID' => (int)($row['ObjectID'] ?? 0)
                ];
            }
        }
        return $out;
    }
}