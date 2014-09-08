<?php
namespace benben\filters;

use benben\base\Component;

class Filter extends Component implements IFilter
{
	/**
	 * Performs the filtering.
	 * The default implementation is to invoke {@link preFilter}
	 * and {@link postFilter} which are meant to be overridden
	 * child classes. If a child class needs to override this method,
	 * make sure it calls <code>$filterChain->run()</code>
	 * if the action should be executed.
	 * @param FilterChain $filterChain the filter chain that the filter is on.
	 */
	public function filter($filterChain)
	{
		if($this->preFilter($filterChain))
		{
			$filterChain->run();
			$this->postFilter($filterChain);
		}
	}
	
	/**
	 * Initializes the filter.
	 * This method is invoked after the filter properties are initialized
	 * and before {@link preFilter} is called.
	 * You may override this method to include some initialization logic.
	 */
	public function init()
	{
	}
	
	/**
	 * Performs the pre-action filtering.
	 * @param FilterChain $filterChain the filter chain that the filter is on.
	 * @return boolean whether the filtering process should continue and the action
	 * should be executed.
	 */
	protected function preFilter($filterChain)
	{
		return true;
	}
	
	/**
	 * Performs the post-action filtering.
	 * @param FilterChain $filterChain the filter chain that the filter is on.
	 */
	protected function postFilter($filterChain)
	{
	}
}
