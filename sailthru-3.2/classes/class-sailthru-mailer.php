<?php

require_once ABSPATH . WPINC . '/class-phpmailer.php';
require_once ABSPATH . WPINC . '/class-smtp.php';


class SailthruMailer extends PHPMailer {

	public $vars          = [];
	public $options       = [];
	public $template      = '';
	public $schedule_time = 'now';
	public $email         = '';


	public function addVar( $key, $value ) {

		if ( ! empty( $key ) ) {
			$this->vars[ $key ] = $value;
		}

	}


	public function sailthruSend() {

		if ( count( $this->getAllRecipientAddresses() ) > 0 ) {
			$this->email = implode( ',', array_keys( $this->getAllRecipientAddresses() ) );
		} else {
			throw new phpmailerException( 'Email address was not provided' );
		}

		// make sure we have a template, if we don't fall back to the default mailer
		if ( empty( $this->template ) ) {
			$this->mailer = 'mail';
		}

		// Add the CC's
		if ( count( $this->getCcAddresses() ) > 0 ) {

			$cc = '';
			foreach ( $this->getCcAddresses() as $email ) {
				$cc .= $email[0] . ',';
			}
			$this->options['headers']['Cc'] = rtrim( $cc, ',' );

		}

		// Add the BCC's
		if ( count( $this->getBccAddresses() ) > 0 ) {

			$bcc = '';
			foreach ( $this->getBccAddresses() as $email ) {
				$bcc .= $email[0] . ',';
			}
			$this->options['headers']['Bcc'] = rtrim( $bcc, ',' );

		}

		// Get the reply To Addresses
		if ( count( $this->getReplyToAddresses() ) > 0 ) {
			$this->options['headers']['replyto'] = implode( ',', array_keys( $this->getReplyToAddresses() ) );
		}

		$this->vars['subject'] = $this->Subject;
		$this->vars['body']    = $this->MIMEBody;

		$sailthru   = get_option( 'sailthru_setup_options' );
		$api_key    = $sailthru['sailthru_api_key'];
		$api_secret = $sailthru['sailthru_api_secret'];
		$client     = new WP_Sailthru_Client( $api_key, $api_secret );

		try {

			$data = array(
				'email'         => $this->email,
				'template'      => $this->template,
				'vars'          => $this->vars,
				'options'       => $this->options,
				'schedule_time' => $this->schedule_time,
			);

			$client->apiPost( 'send', $data );
			return true;
		} catch ( Sailthru_Client_Exception $exc ) {
			print esc_html( $exc );
		}

	}


	public function postSend() {

		try {
			// Choose the mailer and send through it
			switch ( $this->Mailer ) {
				case 'sailthru':
					return $this->sailthruSend();
				case 'sendmail':
				case 'qmail':
					return $this->sendmailSend( $this->MIMEHeader, $this->MIMEBody );
				case 'smtp':
					return $this->smtpSend( $this->MIMEHeader, $this->MIMEBody );
				case 'mail':
					return $this->mailSend( $this->MIMEHeader, $this->MIMEBody );
				default:
					$sendMethod = $this->Mailer . 'Send';
					if ( method_exists( $this, $sendMethod ) ) {
						return $this->$sendMethod( $this->MIMEHeader, $this->MIMEBody );
					}
					return $this->mailSend( $this->MIMEHeader, $this->MIMEBody );
			}
		} catch ( phpmailerException $exc ) {
			$this->setError( $exc->getMessage() );
			$this->edebug( $exc->getMessage() );
			if ( $this->exceptions ) {
				throw $exc;
			}
		}

		return false;
	}


}
