<?php
namespace benben\base;

interface IApplicationComponent
{
    /**
     * Initialized the application component.
     * This method is invoked after the application completes configuration.
     */
    public function init();
    /**
     * @return boolean wheather the {@link init()} method has been invoked
     */
    public function getIsInitialized();
}