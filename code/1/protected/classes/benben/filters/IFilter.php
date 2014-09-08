<?php
namespace benben\filters;

/**
 * IFilter is the interface that must be implemented by action filters.
 *
 * @since 1.0
 */
interface IFilter 
{
	/**
	 * Performs the filtering.
	 * This method should be implemented to perform actual filtering.
	 * If the filter wants to continue the action execution, it should call
	 * <code>$filterChain->run()</code>.
	 * @param FilterChain $filterChain the filter chain that the filter is on.
	 */
	public function filter($filterChain);
}