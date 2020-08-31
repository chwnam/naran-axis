<?php


namespace Naran\Axis\Model;


interface ActivationDeactivation
{
    public function activationSetup();

    public function deactivationCleanup();
}
