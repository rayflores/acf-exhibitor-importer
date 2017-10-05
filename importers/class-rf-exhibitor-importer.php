<?php
/**
 * Exhibitor importer
 *
 * Import exhibitor data and set data to existing rows on Exhibitor List Page
 *
 * @author 		Ray Flores
 * @category 	Admin
 * @package 		Admin/Importers
 * @version     	1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( class_exists( 'WP_Importer' ) ) {
    class RF_Exhibitor_Importer extends WP_Importer {

        var $id;
        var $file_url;
        var $import_page;
        var $delimiter = ",";
        var $posts = array();
        var $total;
        var $imported;
        var $skipped;

        /**
         * __construct function.
         *
         * @access public
         */
        public function __construct() {
            $this->import_page = 'rf_acf_exhibitor_importer';
        }

        /**
         * Registered callback function for the WordPress Importer
         *
         * Manages the three separate stages of the CSV import process
         */
        function dispatch() {
            $this->header();

            $step = empty( $_GET['step'] ) ? 0 : (int) $_GET['step'];
            switch ( $step ) {
                case 0:
                    $this->greet();
                    break;
                case 1:
				    check_admin_referer( 'import-upload' );
                    if ( $this->handle_upload() ) {

                        if ( $this->id )
                            $file = get_attached_file( $this->id );
                        else
                            $file = ABSPATH . $this->file_url;

                        add_filter( 'http_request_timeout', array( $this, 'bump_request_timeout' ) );

                        if ( function_exists( 'gc_enable' ) )
                            gc_enable();

                        @set_time_limit(0);
                        @ob_flush();
                        @flush();

                        $this->import( $file );
                    }
                    break;
            }
            $this->footer();
        }

        /**
         * format_data_from_csv function.
         *
         * @access public
         * @param mixed $data
         * @param string $enc
         * @return string
         */
        function format_data_from_csv( $data, $enc ) {
            return ( $enc == 'UTF-8' ) ? $data : utf8_encode( $data );
        }
		/* search for matching company_name and return the key */
		function searchForCompany($company, $array) {
		   foreach ($array as $key => $val) {
			   if ($val['company_name'] === $company) {
				   return $key;
			   }
		   }
		   return null;
		}
		
		function check_if_customer_exists($all_current_full_names, $field, $check_full_name)
		{
		   foreach($all_current_full_names as $key => $current_name)
		   {
			  if ( $current_name['NAME'] === $check_full_name )
				 return $current_name['ID'];
		   }
		   return false;
		}
		
		function removeRepeaterRow($array, $key, $value){
			 foreach($array as $subKey => $subArray){
				  if($subArray[$key] == $value){
					   unset($array[$subKey]);
				  }
			 }
			 return $array;
		}
		function delete_acf_rows($page, $field){
			// a	companies	field_563ac1f729118
			$companies = (int)get_post_meta( $page, $field , true );
			$i = 0;
			if( $companies > 0 ) {
				while (  $i < $companies ) {
					delete_post_meta($page, $field.'_'.$i.'_company_page_link');
					delete_post_meta($page, '_'.$field.'_'.$i.'_company_page_link');
					delete_post_meta($page, $field.'_'.$i.'_booth_numbers');
					delete_post_meta($page, '_'.$field.'_'.$i.'_booth_numbers');
					delete_post_meta($page, $field.'_'.$i.'_company_name');
					delete_post_meta($page, '_'.$field.'_'.$i.'_company_name');
					$i++;
				}
				delete_post_meta($page,$field);
				delete_post_meta($page,'_'.$field);
			}
		}
        /**
         * import function.
         *
         * @access public
         * @param mixed $file
         * @return void
         */
        function import( $file ) {
			global $wpdb;
			// get pageID that was set in impoter
			$this->page_id = isset($_POST['page_id']) ? $_POST['page_id'] : '0';  
			$pageID = $this->page_id;
			//echo $this->page_id;
			$this->delete_acf_rows($this->page_id, '123-companies');
			$this->delete_acf_rows($this->page_id, 'a-companies');
			$this->delete_acf_rows($this->page_id, 'b-companies');
			$this->delete_acf_rows($this->page_id, 'c-companies');
			$this->delete_acf_rows($this->page_id, 'd-companies');
			$this->delete_acf_rows($this->page_id, 'e-companies');
			$this->delete_acf_rows($this->page_id, 'f-companies');
			$this->delete_acf_rows($this->page_id, 'g-companies');			
			$this->delete_acf_rows($this->page_id, 'h-companies');
			$this->delete_acf_rows($this->page_id, 'i-companies');
			$this->delete_acf_rows($this->page_id, 'j-companies');
			$this->delete_acf_rows($this->page_id, 'k-companies');
			$this->delete_acf_rows($this->page_id, 'l-companies');
			$this->delete_acf_rows($this->page_id, 'm-companies');
			$this->delete_acf_rows($this->page_id, 'n-companies');
			$this->delete_acf_rows($this->page_id, 'o-companies');
			$this->delete_acf_rows($this->page_id, 'p-companies');
			$this->delete_acf_rows($this->page_id, 'q-companies');
			$this->delete_acf_rows($this->page_id, 'r-companies');
			$this->delete_acf_rows($this->page_id, 's-companies');
			$this->delete_acf_rows($this->page_id, 't-companies');
			$this->delete_acf_rows($this->page_id, 'u-companies');
			$this->delete_acf_rows($this->page_id, 'v-companies');
			$this->delete_acf_rows($this->page_id, 'w-companies');
			$this->delete_acf_rows($this->page_id, 'x-companies');
			$this->delete_acf_rows($this->page_id, 'y-companies');
			$this->delete_acf_rows($this->page_id, 'z-companies');
			// ACF - we might need these later?
			$field_objects = get_field_objects($this->page_id); // all ACF fields for page_id
			$this->total = $this->imported = $this->skipped = 0;
            $skipped_report = '';

            if ( ! is_file($file) ) {
                echo '<p><strong>' . __( 'Sorry, there has been an error.', 'rf-exhibitor-importer' ) . '</strong><br />';
                echo __( 'The file does not exist, please try again.', 'rf-exhibitor-importer' ) . '</p>';
                $this->footer();
                die();
            }
			$fp = file($file); // get rows
			
			
            ini_set( 'auto_detect_line_endings', '1' );
				
				$numRows = count($fp); // how many rows in CSV file
				
				
            if ( ( $handle = fopen( $file, "r" ) ) !== FALSE ) {

                $header = fgetcsv( $handle, 0, $this->delimiter );

                if ( sizeof( $header ) == 3 ) {

                    $loop = 0;

                    while ( ( $row = fgetcsv( $handle, 0, $this->delimiter ) ) !== FALSE ) {

                        list( $company_name, $booth_numbers, $company_link ) = $row;
                        // verify pageID is found
						if ( $this->page_id > 0 ) {
							// let's get the first letter of company_name to see what row where are in
							if ($company_name != '' ){
                                 $first_letter_company_name = ucfirst(substr($company_name,0,1));
							}
								/*************************************************/			
								/* LETS ADD THEM ALL IN BY LETTER, NUMBER LAST */
								/*************************************************/
								if( $first_letter_company_name === 'A' ) // first letter is "a"
								{
									
									// a	companies	field_563ac1f729118
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('a-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name == 'B' ) // first letter is "b"
								{
									
									// b companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('b-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name == 'C' ) // first letter is "c"
								{
									
									// c	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('c-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'D' ) // first letter is "d"
								{
									
									// d	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('d-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'E' ) // first letter is "e"
								{
									
									// e	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('e-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'F' ) // first letter is "f"
								{
									
									// f	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('f-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'G' ) // first letter is "g"
								{
									
									// g	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('g-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'H' ) // first letter is "h"
								{
									
									// h	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('h-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'I' ) // first letter is "i"
								{
									
									// i	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('i-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'J' ) // first letter is "j"
								{
									
									// j	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('j-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'K' ) // first letter is "k"
								{
									
									// k	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('k-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'L' ) // first letter is "l"
								{
									
									// l	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('l-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'M' ) // first letter is "m"
								{
									
									// m	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('m-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'N' ) // first letter is "n"
								{
									
									// n	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('n-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'O' ) // first letter is "o"
								{
									
									// o	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('o-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'P' ) // first letter is "p"
								{
									
									// p	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('p-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'Q' ) // first letter is "q"
								{
									
									// q	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('q-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'R' ) // first letter is "r"
								{
										
									// r	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('r-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'S' ) // first letter is "s"
								{
									
									// s	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('s-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'T' ) // first letter is "t"
								{
									
									// t	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('t-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'U' ) // first letter is "u"
								{
										
									// u	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('u-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'V' ) // first letter is "v"
								{
									
									// v	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('v-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'W' ) // first letter is "w"
								{
									
									// w	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('w-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'X' ) // first letter is "x"
								{
									
									// x	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('x-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'Y' ) // first letter is "y"
								{
									
									// y	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('y-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif( $first_letter_company_name === 'Z' ) // first letter is "z"
								{
									
									// z	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('z-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
								} elseif (is_numeric($first_letter_company_name))	
								{ // first_letter_company_name not a letter
								
									// 123	companies
									$add_row_values = array(
										'company_name' => $company_name, 
										'company_page_link' => $company_link, 
										'booth_numbers' => $booth_numbers
										);
									$i = add_row('123-companies', $add_row_values, $this->page_id);

                                $loop++;
                                $this->imported++;
                        } 
                    } // IF NO PAGE ID WAS SET
					else {

                    echo '<p><strong>' . __( 'Sorry, there has been an error.', 'rf-exhibitor-importer' ) . '</strong><br />';
                    echo __( 'No Page ID was set.  Please go back and choose the correct page from the dropdown list.', 'rf-exhibitor-importer' ) . '</p>';
                    $this->footer();
                    die();

					}
				}
					}  // ENDWHILE
				
                fclose( $handle );
            }
			
            // Show Result
            echo '<div class="updated settings-error below-h2"><p>
				'.sprintf( __( 'Import complete: Total rows: <strong>%s</strong>.', 'rf-exhibitor-importer' ),  $this->imported ).'</p></div>';

            $this->import_end();
        }

        /**
         * Performs post-import cleanup of files and the cache
         */
        function import_end() {
            echo '<p>' . __( 'All done!', 'rf-exhibitor-importer' ) . '</p>';

            do_action( 'import_end' );
        }

        /**
         * Handles the CSV upload and initial parsing of the file to prepare for
         * displaying author import options
         *
         * @return bool False if error uploading or invalid file, true otherwise
         */
        function handle_upload() {

            if ( empty( $_POST['file_url'] ) ) {

                $file = wp_import_handle_upload();

                if ( isset( $file['error'] ) ) {
                    echo '<p><strong>' . __( 'Sorry, there has been an error.', 'rf-exhibitor-importer' ) . '</strong><br />';
                    echo esc_html( $file['error'] ) . '</p>';
                    return false;
                }

                $this->id = (int) $file['id'];

            } else {

                if ( file_exists( ABSPATH . $_POST['file_url'] ) ) {

                    $this->file_url = esc_attr( $_POST['file_url'] );

                } else {

                    echo '<p><strong>' . __( 'Sorry, there has been an error.', 'rf-exhibitor-importer' ) . '</strong></p>';
                    return false;

                }

            }

            return true;
        }

        /**
         * header function.
         *
         * @access public
         * @return void
         */
        function header() {
            echo '<div class="wrap"><div class="icon32 icon32-woocommerce-importer" id="icon-woocommerce"><br></div>';
            echo '<h2>' . __( 'Import Exihibitor List', 'rf-exhibitor-importer' ) . '</h2>';
			
        }

        /**
         * footer function.
         *
         * @access public
         * @return void
         */
        function footer() {
            echo '</div>';
        }

        /**
         * greet function.
         *
         * @access public
         * @return void
         */
        function greet() {
			
            echo '<div class="narrow">';
            echo '<p>' . __( 'Howdy! Upload a CSV file containing Exhibitor data to import. Choose a .csv file to upload, then click "Upload file and import".', 'rf-exhibitor-importer' ).'</p>';
            echo '<p>' . __( 'The file needs to have three columns: Company Name, Booth Location, Company Link', 'rf-exhibitor-importer' ) . '</p>';

            $action = 'admin.php?import=rf_acf_exhibitor_importer&step=1';
			
            $bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
            $size = size_format( $bytes );
            $upload_dir = wp_upload_dir();
            if ( ! empty( $upload_dir['error'] ) ) :
                ?><div class="error"><p><?php _e( 'Before you can upload your import file, you will need to fix the following error:', 'rf-exhibitor-importer' ); ?></p>
                <p><strong><?php echo $upload_dir['error']; ?></strong></p></div><?php
            else :
                ?>
                <form enctype="multipart/form-data" id="import-upload-form" method="post" action="<?php echo esc_attr(wp_nonce_url($action, 'import-upload')); ?>">
                    <table class="form-table">
                        <tbody>
						<tr>
							<th>
								<label for="pages"><?php _e( 'Choose which page has the Exhibitor List:','rf-exhibitor-importer'); ?></label>
							</th>
							<td>
								<?php wp_dropdown_pages( array (
									'show_option_none' => 'Select Page with Exhibitors List',
								)); ?>
							</td>	
						</tr>
                        <tr>
                            <th>
                                <label for="upload"><?php _e( 'Choose a file from your computer:', 'rf-exhibitor-importer' ); ?></label>
                            </th>
                            <td>
                                <input type="file" id="upload" name="import" size="25" />
                                <input type="hidden" name="action" value="save" />
                                <input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />
                                <small><?php printf( __('Maximum size: %s', 'rf-exhibitor-importer' ), $size ); ?></small>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button" value="<?php esc_attr_e( 'Upload file and import', 'rf-exhibitor-importer' ); ?>" />
                    </p>
                </form>
            <?php
            endif;

            echo '</div>';
        }

        /**
         * Added to http_request_timeout filter to force timeout at 60 seconds during import
         * @param  int $val
         * @return int 60
         */
        function bump_request_timeout( $val ) {
            return 300;
        }
    }
}
