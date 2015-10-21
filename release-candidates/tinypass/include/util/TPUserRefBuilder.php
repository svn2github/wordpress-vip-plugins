<?php

/**
 * Copyright (c) 2014, Tinypass, Inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or
 * without modification, are permitted provided that the following
 * conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in the
 * documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
 * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
class TPUserRefBuilder {
	const FIRST_NAME = 'first_name';
	const LAST_NAME = 'last_name';
	const UID = 'uid';
	const EMAIL = 'email';
	const CREATE_DATE = 'create_date';
	const TIMESTAMP = 'timestamp';

	private $data = array();

	/**
	 * Instantiates a new TPUserRefBuilder; Typically you'll want to use the
	 * static {@link TPUserRefBuilder::create()} method
	 *
	 * @param string $uid The user's globally unique id, typically the numeric auto-incremented identifier
	 * @param string $email The user's email; needs to be globally unique
	 */
	public function __construct( $uid, $email ) {
		$this->set( self::UID, $uid );
		$this->set( self::EMAIL, $email );

		return $this;
	}

	/**
	 * Creates a new TPUserRefBuilder
	 *
	 * @param string $uid The user's globally unique id, typically the numeric auto-incremented identifier
	 * @param string $email The user's email; needs to be globally unique
	 *
	 * @return TPUserRefBuilder
	 */
	public static function create( $uid, $email ) {
		return new TPUserRefBuilder( $uid, $email );
	}

	/**
	 * Sets the (optional) first name for the user.
	 *
	 * @param string $firstName The user's first name
	 *
	 * @return TPUserRefBuilder
	 */
	public function setFirstName( $firstName ) {
		$this->set( self::FIRST_NAME, $firstName );

		return $this;
	}

	/**
	 * Sets the (optional) last name for the user.
	 *
	 * @param string $lastName The user's last name
	 *
	 * @return TPUserRefBuilder
	 */
	public function setLastName( $lastName ) {
		$this->set( self::LAST_NAME, $lastName );

		return $this;
	}

	/**
	 * Sets the (optional) creation (registration) date of the user
	 *
	 * @param string $createDate The user's date of creation (registration)
	 *
	 * @return TPUserRefBuilder
	 */
	public function setCreateDate( $createDate ) {
		$this->set( self::CREATE_DATE, $createDate );

		return $this;
	}

	/**
	 * Generically set other properties on the user ref object
	 *
	 * @param string $key The key to set
	 * @param string $value The value to set
	 *
	 * @return TPUserRefBuilder
	 */
	public function set( $key, $value ) {
		$this->data[ $key ] = $value;

		return $this;
	}


	/**
	 * Builds the encrypted user ref string.
	 *
	 * @param string $privateKey The private key to use when encrypting the user ref
	 *
	 * @return string
	 * @throws Exception
	 */
	public function build( $privateKey ) {
		$this->set( self::TIMESTAMP, time() );

		return TPSecurityUtils::encrypt( $privateKey, json_encode( $this->data ) );
	}
}