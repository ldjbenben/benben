<?php
namespace benben\log;

interface ILogRoute
{
    /**
     * Processes log messages and sends them to specific destination.
     * Derived child classes must implements this method.
     * @param array $logs list of messages. Each array elements represents one message 
     * with the following structure:
     * array(
     *     ['msg'] => message (string),
     *     ['level'] => level (string),
     *     ['category'] => category (string),
     *     ['timestamp'] => timestamp (flat, obtained by microtime(true))
     * )
     */
    function log($logs);
}