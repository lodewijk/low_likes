<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
include(PATH_THIRD.'low_likes/config.php');

/**
 * Low Likes Module class
 *
 * @package        low_likes
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-likes
 * @copyright      Copyright (c) 2012, Low
 */
class Low_likes {

	// --------------------------------------------------------------------
	// PROPERTIES
	// --------------------------------------------------------------------

	/**
	 * EE Superobject
	 *
	 * @access      private
	 * @var         object
	 */
	private $EE;

	/**
	 * Shortcut to current member_id, which we'll need a lot
	 *
	 * @access      private
	 * @var         int
	 */
	private $member_id;

	// --------------------------------------------------------------------
	// METHODS
	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access     public
	 * @return     void
	 */
	public function __construct()
	{
		// --------------------------------------
		// Get global object
		// --------------------------------------

		$this->EE =& get_instance();

		// --------------------------------------
		// Set shortcut
		// --------------------------------------

		$this->member_id = $this->EE->session->userdata('member_id');
	}

	// --------------------------------------------------------------------

	/**
	 * Show Likes for given entry, possibly wrapped around a form tag
	 *
	 *
	 */
	public function show()
	{
		// --------------------------------------
		// Initiate return data
		// --------------------------------------

		$tagdata = $this->EE->TMPL->tagdata;

		// --------------------------------------
		// Entry ID is needed
		// --------------------------------------

		if ( ! ($entry_id = $this->EE->TMPL->fetch_param('entry_id')))
		{
			// Make a note of it in the template log
			$this->EE->TMPL->log_item('Low Likes: no entry_id given in Form tag');

			// And return raw data
			return $tagdata;
		}

		// --------------------------------------
		// Are we showing a form later on?
		// --------------------------------------

		$form = (($this->EE->TMPL->fetch_param('form') == 'yes') && $this->member_id);

		// --------------------------------------
		// Get all Likes for this entry
		// --------------------------------------

		// Initiate likes array
		$likes = $this->EE->session->cache(LOW_LIKES_PACKAGE, 'likes');

		$likes = isset($likes[$entry_id]) ? $likes[$entry_id] : array();

		// Compose variables for tagdata
		$vars = array(
			'total_likes' => count($likes),
			'is_liked'    => in_array($this->member_id, $likes),
			'has_form'    => $form
		);

		// Parse the tagdata
		$tagdata = $this->EE->TMPL->parse_variables_row($tagdata, $vars);

		// --------------------------------------
		// Are we showing a form?
		// --------------------------------------

		if ($form)
		{
			// Initiate data array for form creation
			$data = array(
				'id'    => $this->EE->TMPL->fetch_param('form_id'),
				'class' => $this->EE->TMPL->fetch_param('form_class')
			);

			// Define default hidden fields
			$data['hidden_fields'] = array(
				'ACT' => $this->EE->functions->fetch_action_id(LOW_LIKES_PACKAGE, 'toggle_like'),
				'EID' => $entry_id
			);

			// Wrap form around tagdata
			$tagdata = $this->EE->functions->form_declaration($data) . $tagdata . '</form>';
		}

		// --------------------------------------
		// Return output: parsed tagdata
		// --------------------------------------

		return $tagdata;
	}

	// --------------------------------------------------------------------

	/**
	 * ACT: (un)like posted entry
	 *
	 * @access     public
	 * @return     void
	 */
	public function toggle_like()
	{
		// --------------------------------------
		// Get entry id from post, member_id from session
		// --------------------------------------

		$entry_id = $this->EE->input->post('EID');

		// --------------------------------------
		// Only continue if we have an entry_id and a member_id
		// --------------------------------------

		if ($entry_id && $this->member_id)
		{
			// Data to work with
			$data = array(
				'entry_id'  => $entry_id,
				'member_id' => $this->member_id
			);

			// Liked or not?
			$this->EE->db->where($data);
			$liked = $this->EE->db->count_all_results('low_likes');

			if ($liked)
			{
				$this->EE->db->delete('low_likes', $data);
			}
			else
			{
				$data['like_date'] = $this->EE->localize->now;
				$this->EE->db->insert('low_likes', $data);
			}

			// Cater for Ajax requests
			if (AJAX_REQUEST)
			{
				die($liked ? '-1' : '1');
			}
		}

		// --------------------------------------
		// Go back to where you came from
		// --------------------------------------

		$this->EE->functions->redirect($this->EE->session->tracker[1]);
	}

} // End Class

/* End of file mod.low_likes.php */