<?php

namespace Shy\Forms;



/**
 * An index that points into an array.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
interface Index
{
	/**
	 * Look it up.
	 * @param array $in
	 * @return mixed
	 */
	public function lookup(array $in);

	/**
	 * Format this index as name for HTML controls.
	 * @return string
	 */
	public function get_html_name();
}
