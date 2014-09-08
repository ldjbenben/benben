<?php
namespace benben\cache\dependence;

/**
 * ICacheDependency is the interface that must be implemented by cache dependency classes.
 * 
 * This interface must be implemented by classes meant to be used as cache dependencies.
 * 
 * Objects implementing this interface must be able to be serialized and unserialized.
 * 
 * @author benben
 *
 */
interface ICacheDependcy
{
    /**
     * Evaluates the dependency by generating and saving the data related with dependency.
     * This method is invoked by cache before writing data into it.
     */
    function evaluateDependency();
    /**
     * @return boolean whether the dependency has changed.
    */
    function getHasChanged();
}