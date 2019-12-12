<?php
namespace OliverHader\TYPO3Remote;

class TransientConnection extends \Doctrine\DBAL\Connection
{
    public function connect()
    {
        return false;
    }
}