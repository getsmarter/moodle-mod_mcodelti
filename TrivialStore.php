<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
//
// This file is part of Basicmcodelti4Moodle
//
// Basicmcodelti4Moodle is an IMS Basicmcodelti (Basic Learning Tools for Interoperability)
// consumer for Moodle 1.9 and Moodle 2.0. Basicmcodelti is a IMS Standard that allows web
// based learning tools to be easily integrated in LMS as native ones. The IMS Basicmcodelti
// specification is part of the IMS standard Common Cartridge 1.1 Sakai and other main LMS
// are already supporting or going to support Basicmcodelti. This project Implements the consumer
// for Moodle. Moodle is a Free Open source Learning Management System by Martin Dougiamas.
// Basicmcodelti4Moodle is a project iniciated and leaded by Ludo(Marc Alier) and Jordi Piguillem
// at the GESSI research group at UPC.
// Simplemcodelti consumer for Moodle is an implementation of the early specification of mcodelti
// by Charles Severance (Dr Chuck) htp://dr-chuck.com , developed by Jordi Piguillem in a
// Google Summer of Code 2008 project co-mentored by Charles Severance and Marc Alier.
//
// Basicmcodelti4Moodle is copyright 2009 by Marc Alier Forment, Jordi Piguillem and Nikolas Galanis
// of the Universitat Politecnica de Catalunya http://www.upc.edu
// Contact info: Marc Alier Forment granludo @ gmail.com or marc.alier @ upc.edu.

/**
 * This file contains a Trivial memory-based store - no support for tokens
 *
 * @package mod_mcodelti
 * @copyright IMS Global Learning Consortium
 *
 * @author Charles Severance csev@umich.edu
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

namespace moodle\mod\mcodelti; // Using a namespace as the basicmcodelti module imports classes with the same names.

defined('MOODLE_INTERNAL') || die;

/**
 * A Trivial memory-based store - no support for tokens.
 */
class TrivialOAuthDataStore extends OAuthDataStore {

    /** @var array $consumers  Array of tool consumer keys and secrets */
    private $consumers = array();

    /**
     * Add a consumer to the array
     *
     * @param string $consumerkey     Consumer key
     * @param string $consumersecret  Consumer secret
     */
    public function add_consumer($consumerkey, $consumersecret) {
        $this->consumers[$consumerkey] = $consumersecret;
    }

    /**
     * Get OAuth consumer given its key
     *
     * @param string $consumerkey     Consumer key
     *
     * @return moodle\mod\mcodelti\OAuthConsumer  OAuthConsumer object
     */
    public function lookup_consumer($consumerkey) {
        if (strpos($consumerkey, "http://" ) === 0) {
            $consumer = new OAuthConsumer($consumerkey, "secret", null);
            return $consumer;
        }
        if ( $this->consumers[$consumerkey] ) {
            $consumer = new OAuthConsumer($consumerkey, $this->consumers[$consumerkey], null);
            return $consumer;
        }
        return null;
    }

    /**
     * Create a dummy OAuthToken object for a consumer
     *
     * @param moodle\mod\mcodelti\OAuthConsumer $consumer     Consumer
     * @param string $tokentype    Type of token
     * @param string $token        Token ID
     *
     * @return moodle\mod\mcodelti\OAuthToken OAuthToken object
     */
    public function lookup_token($consumer, $tokentype, $token) {
        return new OAuthToken($consumer, '');
    }

    /**
     * Nonce values are not checked so just return a null
     *
     * @param moodle\mod\mcodelti\OAuthConsumer $consumer     Consumer
     * @param string $token        Token ID
     * @param string $nonce        Nonce value
     * @param string $timestamp    Timestamp
     *
     * @return null
     */
    public function lookup_nonce($consumer, $token, $nonce, $timestamp) {
        // Should add some clever logic to keep nonces from
        // being reused - for now we are really trusting
        // that the timestamp will save us.
        return null;
    }

    /**
     * Tokens are not used so just return a null.
     *
     * @param moodle\mod\mcodelti\OAuthConsumer $consumer     Consumer
     *
     * @return null
     */
    public function new_request_token($consumer) {
        return null;
    }

    /**
     * Tokens are not used so just return a null.
     *
     * @param string $token        Token ID
     * @param moodle\mod\mcodelti\OAuthConsumer $consumer     Consumer
     *
     * @return null
     */
    public function new_access_token($token, $consumer) {
        return null;
    }
}
