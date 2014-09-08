<?php
namespace application\components;
use benben\filters\Filter;
use benben\Benben;

class AuthFilter extends Filter
{
    /**
     * Performs the pre-action filtering.
     * @param FilterChain $filterChain the filter chain that the filter is on.
     * @return boolean whether the filtering process should continue and the action
     * should be executed.
     */
    protected function preFilter($filterChain)
    {
        if(Benben::app()->user->isGuest)
        {
            Benben::app()->user->loginRequired();
            return false;
        }
        
        return true;
    }
}