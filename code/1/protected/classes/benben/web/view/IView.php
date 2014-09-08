<?php

namespace benben\web\view;

interface IView
{
	public function setData($data);
    public function render($template);
    public function renderPartial($template);
}