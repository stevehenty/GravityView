<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * A collection of \GV\Form objects.
 */
class Form_Collection extends Collection {
	/**
	 * Add a \GV\Form to this collection.
	 *
	 * @param \GV\Form $form The form to add to the internal array.
	 *
	 * @throws \InvalidArgumentException if $form is not of type \GV\Form.
	 *
	 * @api
	 * @since future
	 * @return void
	 */
	public function add( $form ) {
		if ( ! $form instanceof Form ) {
			throw new \InvalidArgumentException( 'Form_Collections can only contain objects of type \GV\Form.' );
		}
		parent::add( $form );
	}

	/**
	 * Get a \GV\Form from this list.
	 *
	 * @param int $form_id The ID of the form to get.
	 * @param string $backend The form backend identifier, allows for multiple form backends in the future. Unused until then.
	 *
	 * @api
	 * @since future
	 *
	 * @return \GV\Form|null The \GV\Form with the $form_id as the ID, or null if not found.
	 */
	public function get( $form_id, $backend = 'gravityforms' ) {
		foreach ( $this->all() as $form ) {
			if ( $form->ID == $form_id ) {
				return $form;
			}
		}
		return null;
	}
}
