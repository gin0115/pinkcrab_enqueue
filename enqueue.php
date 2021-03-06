<?php declare(strict_types=1);
/**
 * A chainable helper class for enqueuing scripts and styles.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab
 * @version 1.0.0
 */

namespace PinkCrab;

/**
 * WordPress Script and Style enqueuing class.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 */
final class Enqueue {

	/**
	 * The handle to enqueue the script or style with.
	 * Also used for any locaized variables.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $handle;

	/**
	 * The type of file to enqueue.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $type;

	/**
	 * The file loaction (URI)
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $src = '';

	/**
	 * Dependencies which must be loaded prior.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $deps = array();

	/**
	 * Version tag for file enqueued
	 *
	 * @since 1.0.0
	 * @var mixed
	 */
	protected $ver = false;

	/**
	 * Defines if script should be loaded in footer (true) or header (false)
	 *
	 * @since 1.0.0
	 * @var boolean
	 */
	protected $footer = true;

	/**
	 * Values to be localized when script enqueued.
	 *
	 * @since 1.0.0
	 * @var array|null
	 */
	protected $localize;

	/**
	 * Defines if script should be parsed inline or enqueued.
	 * Please note this should only be used for simple and small JS files.
	 *
	 * @since 1.0.0
	 * @var boolean
	 */
	protected $inline = false;

	/**
	 * Style sheet which has been defined.
	 * Accepts media types like wp_enqueue_styles.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $media = 'all';

	/**
	 * If file should be loaded on the front end.
	 *
	 * @since 1.0.0
	 * @var boolean
	 */
	protected $front = true;

	/**
	 * Defines if file should be loaded in wp-admin
	 *
	 * @since 1.0.0
	 * @var boolean
	 */
	protected $admin = true;

	/**
	 * Creates an Enqueue instance.
	 *
	 * @since 1.0.0
	 * @param string $handle
	 * @param string $type
	 */
	public function __construct( string $handle, string $type ) {
		$this->handle = $handle;
		$this->type   = $type;
	}

	/**
	 * Creates a static instace of the Enqueue class for a script.
	 *
	 * @since 1.0.0
	 * @param string $handle
	 * @return self
	 */
	public static function script( string $handle ): self {
		return new self( $handle, 'script' );
	}

	/**
	 * Creates a static instace of the Enqueue class for a style.
	 *
	 * @since 1.0.0
	 * @param string $handle
	 * @return self
	 */
	public static function style( string $handle ): self {
		return new self( $handle, 'style' );
	}

	/**
	 * Enqueue in wp-admin (ajax/rest)
	 *
	 * @since 1.0.0
	 * @param boolean $admin
	 * @return self
	 */
	public function admin( bool $admin = true ): self {
		$this->admin = $admin;
		return $this;
	}

	/**
	 * Enqueue in frontend only.
	 *
	 * @since 1.0.0
	 * @param boolean $front
	 * @return self
	 */
	public function front( bool $front = true ): self {
		$this->front = $front;
		return $this;
	}

	/**
	 * Defined the SRC of the file.
	 *
	 * @since 1.0.0
	 * @param string $src
	 * @return self
	 */
	public function src( string $src ): self {
		$this->src = $src;
		return $this;
	}

	/**
	 * Defined the Dependencies of the enqueue.
	 *
	 * @since 1.0.0
	 * @param string ...$deps
	 * @return self
	 */
	public function deps( string ...$deps ): self {
		$this->deps = $deps;
		return $this;
	}

	/**
	 * Defined the version of the enqueue
	 *
	 * @since 1.0.0
	 * @param string $ver
	 * @return self
	 */
	public function ver( string $ver ): self {
		$this->ver = $ver;
		return $this;
	}

	/**
	 * Define the media type.
	 *
	 * @since 1.0.0
	 * @param string $media
	 * @return self
	 */
	public function media( string $media ): self {
		$this->media = $media;
		return $this;
	}

	/**
	 * Sets the version as last modified file time.
	 *
	 * @since 1.0.0
	 * @return self
	 */
	public function lastest_version(): self {
		if ( $this->does_file_exist( $this->src ) ) {
			$this->ver = strtotime( get_headers( $this->src, 1 )['Last-Modified'] );
		}
		return $this;
	}

	/**
	 * Checks to see if a file exist using URL (not path).
	 *
	 * @since 1.0.0
	 * @param string $url The URL of the file being checked.
	 * @return boolean true if it does, false if it doesnt.
	 */
	private function does_file_exist( string $url ): bool {
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_NOBODY, true );
		curl_setopt( $ch, CURLOPT_TIMEOUT_MS, 50 );
		curl_exec( $ch );
		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );
		return $http_code === 200;
	}

	/**
	 * Should the script be called in the footer.
	 *
	 * @since 1.0.0
	 * @param boolean $footer
	 * @return self
	 */
	public function footer( bool $footer = true ): self {
		$this->footer = $footer;
		return $this;
	}

	/**
	 * Should the script be called in the header.
	 *
	 * @since 1.0.0
	 * @return self
	 */
	public function header(): self {
		$this->footer = false;
		return $this;
	}

	/**
	 * Should the script be called in the inline.
	 *
	 * @since 1.0.0
	 * @param boolean $inline
	 * @return self
	 */
	public function inline( bool $inline = true ):self {
		$this->inline = $inline;
		return $this;
	}

	/**
	 * Pass any key => value pairs to be localised with the enqueue.
	 *
	 * @since 1.0.0
	 * @param array $args
	 * @return self
	 */
	public function localize( array $args ): self {
		$this->localize = $args;
		return $this;
	}

	/**
	 * Registers the file as either enqueued or inline parsed.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register(): void {
		if ( $this->type === 'script' ) {
			$this->register_script();
		}

		if ( $this->type === 'style' ) {
			$this->register_style();
		}
	}

	/**
	 * Regsiters the style.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_style() {
		wp_enqueue_style(
			$this->handle,
			$this->src,
			$this->deps,
			$this->ver,
			$this->media
		);
	}

	/**
	 * Registers and enqueues or inlines the script, with any passed localised data.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_script() {

		if ( $this->inline ) {
			wp_register_script(
				$this->handle,
				'',
				$this->deps,
				$this->ver,
				$this->footer
			);
			if ( $this->does_file_exist( $this->src ) ) {
				wp_add_inline_script( $this->handle, file_get_contents( $this->src ) );
			}
		} else {
			wp_register_script(
				$this->handle,
				$this->src,
				$this->deps,
				$this->ver,
				$this->footer
			);
		}

		if ( ! empty( $this->localize ) ) {
			wp_localize_script( $this->handle, $this->handle, $this->localize );
		}

		wp_enqueue_script( $this->handle );
	}
}
