<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Controller default yang menampilkan halaman welcome (tidak berhubungan dengan API JWT)
class Welcome extends CI_Controller {

	/**
	 * Halaman index default.
	 * Digunakan hanya untuk tampilan awal/halaman demo, bukan bagian dari API.
	 */
	public function index()
	{
		// Muat view welcome_message untuk antarmuka sederhana
		$this->load->view('welcome_message');
	}
}
