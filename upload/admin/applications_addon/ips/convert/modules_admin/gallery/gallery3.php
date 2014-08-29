<?php
/**
 * IPS Converters
 * IP.Gallery 3.0 Converters
 * Gallery 3 (Menalto Gallery 3)
 *
 * @package		IPS Converters
 */


	$info = array(
		'key'	=> 'gallery3',
		'name'	=> 'Menalto Photo Gallery 3',
		'login'	=> false,
	);

	class admin_convert_gallery_gallery3 extends ipsCommand
	{
        /**
         * Main class entry point
         *
         * @access    public
         * @param ipsRegistry $registry
         * @internal param \ipsRegistry $object
         * @return    void
         */
	    public function doExecute( ipsRegistry $registry )
		{
			$this->registry = $registry;
			//-----------------------------------------
			// What can this thing do?
			//-----------------------------------------

			$this->actions = array(
				'forum_perms'	=> array(),
				'groups' 		=> array('forum_perms'),
				'members'		=> array('groups'),
				//'gallery_form_fields'	=> array(),
				'gallery_categories'	=> array('members'),
				'gallery_albums'		=> array('members', 'gallery_categories'),
				'gallery_images'		=> array('members', 'gallery_categories', 'gallery_albums'),
				'gallery_comments'		=> array('members', 'gallery_images'),
				);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_gallery.php' );
            $html = '';
            $this->lib =  new lib_gallery( $this->registry, $html, $this, false );

	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'Gallery 3 Photo Gallery &rarr; IP.Gallery Converter' );

			//-----------------------------------------
			// Are we connected?
			// (in the great circle of life...)
			//-----------------------------------------

			$this->HB = $this->lib->connect();

			//-----------------------------------------
			// What are we doing?
			//-----------------------------------------

			if (array_key_exists($this->request['do'], $this->actions))
			{
				call_user_func(array($this, 'convert_'.$this->request['do']));
			}
			else
			{
				$this->lib->menu();
			}

			//-----------------------------------------
	        // Pass to CP output hander
	        //-----------------------------------------

			$this->sendOutput();

		}

		/**
	    * Output to screen and exit
	    *
	    * @access	private
	    * @return	void
	    */
		private function sendOutput()
		{
			$this->registry->output->html .= $this->html->convertFooter();
			$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
			$this->registry->output->sendOutput();
			exit;
		}

		/**
		 * Count rows
		 *
		 * @access	private
		 * @param 	string		action (e.g. 'members', 'forums', etc.)
		 * @return 	integer 	number of entries
		 **/
		public function countRows($action)
		{
			switch ($action)
			{
				case 'forum_perms':
				case 'groups':
					return $this->lib->countRows('groups');
					break;

				case 'members':
					return $this->lib->countRows('users');
					break;

				/*case 'gallery_form_fields':
					return count($this->_getCustomFields());
					break;*/

				case 'gallery_categories':

                    ipsRegistry::DB('hb')->allow_sub_select=1;

					return $this->lib->countRows('items as pit', "type = 'album' AND (select count(*) as num from items as sit where type = 'photo' and sit.parent_id = pit.id) <= 1");
					break;

				case 'gallery_albums':
                    ipsRegistry::DB('hb')->allow_sub_select=1;

                    return $this->lib->countRows('items as pit', "type = 'album' AND (select count(*) as num from items as sit where type = 'photo' and sit.parent_id = pit.id) > 1");
					break;

				case 'gallery_images':
					return $this->lib->countRows('items', "type='photo'");
					break;

				case 'gallery_comments':
					return $this->lib->countRows('comments');
					break;

				default:
					return $this->lib->countRows($action);
					break;
			}
		}

		/**
		 * Check if section has configuration options
		 *
		 * @access	private
		 * @param 	string		action (e.g. 'members', 'forums', etc.)
		 * @return 	boolean
		 **/
		public function checkConf($action)
		{
			switch ($action)
			{
				case 'forum_perms':
				case 'groups':
				case 'members':
				case 'gallery_albums':
				case 'gallery_images':
					return true;
					break;

				default:
					return false;
					break;
			}
		}

		/**
		 * Fix post data
		 *
		 * @access	private
		 * @param 	string		raw post data
		 * @return 	string		parsed post data
		 **/
		private function fixPostData($post)
		{
			return $post;
		}

		/**
		 * Convert forum permissions
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_forum_perms()
		{
			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$this->lib->saveMoreInfo('forum_perms', 'map');

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'groups',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------

			$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'id', 'nf' => 'name'));

			//---------------------------
			// Loop
			//---------------------------

			foreach( $loop as $row )
			{
				$this->lib->convertPermSet($row['id'], $row['name']);
			}

			$this->lib->next();

		}

		/**
		 * Convert groups
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_groups()
		{
			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$this->lib->saveMoreInfo('groups', 'map');

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'groups',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------

			$this->lib->getMoreInfo('groups',
                                    $loop,
                                    array('new' => '--Create new group--',
                                          'ot' => 'Old group',
                                          'nt' => 'New group'),
                                    '',
                                    array('idf' => 'id', 'nf' => 'name'));

			//---------------------------
			// Loop
			//---------------------------

			foreach( $loop as $row )
			{
				$prefix = '';
				$suffix = '';
				if ($row['group_colour'])
				{
					$prefix = "<span style='color:{$row['group_color']}'>";
					$suffix = '</span>';
				}

				$save = array(
					'g_title'			=> $row['name'],
					'g_max_diskspace'	=> $row['group_quota'],
					'g_access_cp'		=> $row['has_admin_access'],
					//'g_rate'			=> $row['can_rate_pictures'],
					//'g_ecard'			=> $row['can_send_ecards'],
					//'g_comment'			=> $row['can_post_comments'],
					'g_create_albums'	=> $row['can_create_albums'],
					'g_perm_id'			=> $row['id'],
					);
				$this->lib->convertGroup($row['id'], $save);
			}

			$this->lib->next();

		}

		/**
		 * Convert members
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_members()
		{

			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

            $pcpf = array(
            //    'user_icq'		=> 'ICQ Number',
            //    'user_homepage'	=> 'Website',
            );

            // no mapping of additional fields: (this conversion rather skips members, it does not re-create them)
            // $this->lib->saveMoreInfo('members', array_keys($pcpf));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'users',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('members', $main);

			//-----------------------------------------
			// Tell me what you know!
			//-----------------------------------------


			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{

				//-----------------------------------------
				// Set info
				//-----------------------------------------

				$info = array(
					'id'				=> $row['id'],
					'group'				=> $row['user_group'],
					'joined'			=> strtotime($row['user_regdate']),
					'username'			=> $row['user_name'],
					'email'				=> $row['user_email'],
					'md5pass'			=> $row['user_password'],
					);

				$members = array('last_visit'		=> strtotime($row['user_regdate']));
				$profile = array();

				//-----------------------------------------
				// Custom Profile fields
				//-----------------------------------------

				$custom = array();

				//-----------------------------------------
				// And go!
				//-----------------------------------------

                // no member conversion, rather we try a member mapping?
				// $this->lib->convertMember($info, $members, $profile, $custom, '');

			}

            // we add one virtual members link to be able to use an album owner id during album conversion:
            $this->lib->addLink(1, 1, 'members', 1);

            // manually map gallery members to existing ipb members (since there are only a few...)
            // addLink($ipb_id, $foreign_id, $type, $dupe='0')
            $this->lib->addLink(1   ,      1, 'members', 1); // guest
            $this->lib->addLink(1   ,      2, 'members', 1); // admin
            $this->lib->addLink(2   ,      3, 'members', 1); // Frodo
            $this->lib->addLink(3   ,      4, 'members', 1); // wm
            $this->lib->addLink(80  ,      5, 'members', 1); // Elbereth
            $this->lib->addLink(6   ,      6, 'members', 1); // Anastasia
            $this->lib->addLink(20  ,      7, 'members', 1); // Caivallon
            $this->lib->addLink(566 ,      8, 'members', 1); // Eldhwen
            $this->lib->addLink(71  ,      9, 'members', 1); // Balthor der Geweihte
            $this->lib->addLink(320 ,     10, 'members', 1); // Emi
            $this->lib->addLink(456 ,     11, 'members', 1); // illsister
            $this->lib->addLink(803 ,     12, 'members', 1); // Vana
            $this->lib->addLink(107 ,     13, 'members', 1); // Mondkalb
            $this->lib->addLink(1155,     14, 'members', 1); // caz_zweiunachzich
            $this->lib->addLink(627 ,     15, 'members', 1); // Celebrian
            $this->lib->addLink(1958,     16, 'members', 1); // Sethai
            $this->lib->addLink(1   ,     17, 'members', 1); // Nyria
            $this->lib->addLink(933 ,     18, 'members', 1); // A_Brandybuck
            $this->lib->addLink(1196,     19, 'members', 1); // Urubaxi
            $this->lib->addLink(2248,     20, 'members', 1); // Isilya
            $this->lib->addLink(1442,     21, 'members', 1); // andre
            $this->lib->addLink(1   ,     22, 'members', 1); // aljena
            $this->lib->addLink(2951,     23, 'members', 1); // Dragonmaker
            $this->lib->addLink(106 ,     24, 'members', 1); // Úmarth
            $this->lib->addLink(1629,     25, 'members', 1); // Anca
            $this->lib->addLink(2176,     26, 'members', 1); // golwin
            $this->lib->addLink(812 ,     27, 'members', 1); // Iluvatar
            $this->lib->addLink(3556,     28, 'members', 1); // Malbeth
            $this->lib->addLink(623 ,     29, 'members', 1); // Merry
            $this->lib->addLink(2089,     30, 'members', 1); // beleg
            $this->lib->addLink(366 ,     31, 'members', 1); // Elentári
            $this->lib->addLink(2542,     32, 'members', 1); // kamaaina
            $this->lib->addLink(2498,     33, 'members', 1); // Alatariel
            $this->lib->addLink(716 ,     34, 'members', 1); // Thuringwethil
            $this->lib->addLink(1   ,     35, 'members', 1); // Ordwergar
            $this->lib->addLink(5075,     36, 'members', 1); // Fangli


			$this->lib->next();

		}

        /**
         * Convert Categories
         *
         * @access	private
         * @return void
         **/
        private function convert_gallery_categories()
        {

            //---------------------------
            // Set up
            //---------------------------

            $main = array(	'select' 	=> '*',
                'from' 		=> 'items as pit',
                'where'     => "type = 'album' AND (select count(*) as num from items as sit where type = 'photo' and sit.parent_id = pit.id) <= 1",
                'order'		=> 'pit.level, pit.id ASC',
            );

            ipsRegistry::DB('hb')->allow_sub_select=1;

            $loop = $this->lib->load('gallery_categories', $main);

            //---------------------------
            // Loop
            //---------------------------

            while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
            {
                // Convert categories to Albums, but disallow images to be uploaded directly (container mode)
                $save = array (
                    'category_name'				=> $row['title'],
                    'category_description'		=> $row['description'],
                    'category_type'				=> 1,
                    'category_parent_id'		=> ($row['parent_id'] ? $row['parent_id'] : 0), // $row['parent_id'],
                );

                $this->lib->convertCategory($row['id'], $save, array());
            }

            $this->lib->next();

        }

		/**
		 * Convert Albums
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_gallery_albums()
		{
			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$this->lib->saveMoreInfo('gallery_albums', array('container_cat'));

			//---------------------------
			// Set up
			//---------------------------

            $main = array(	'select' 	=> '*',
                'from' 		=> 'items as pit',
                'where'     => "type = 'album' AND (select count(*) as num from items as sit where type = 'photo' and sit.parent_id = pit.id) > 1",
                'order'		=> 'pit.level, pit.id ASC',
            );

            ipsRegistry::DB('hb')->allow_sub_select=1;



            $loop = $this->lib->load('gallery_albums', $main);

			//-----------------------------------------
			// We need to know how to handle orphans
			//-----------------------------------------

            $cats = array();
            $this->DB->build(array('select' => '*', 'from' => 'gallery_categories', 'where' => 'category_type = 1'));
            $this->DB->execute();
            while ($r = $this->DB->fetch())
            {
                $cats[$r['category_id']] = $r['category_name'];
            }

            $this->lib->getMoreInfo('container_cat', $loop, array('orphans' => array('type' => 'dropdown', 'label' => 'To which category do you wish to put members albums, or albums with no category?', 'options' => $cats)));

            $get = unserialize($this->settings['conv_extra']);
            $us = $get[$this->lib->app['name']];

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
                $skip_cat_link = false;

                // can we map the parent category? if not use fallback:
                $parent_id = $row['parent_id'];
                if (!$this->lib->getLink( $row['parent_id'], 'gallery_categories', true ) )
                {
                    $skip_cat_link = true;
                    $parent_id = $us['container_cat'];

                    // workaround if the config does not show??
                    if(!$parent_id)
                    {
                        // 15	0	Gallery3 Conversion
                        $parent_id = 15;
                    }
                }

				$member_id = 1; // use member id = 1 as new default owner (we did not convert/merge members)

				$save = array(
					'album_name'		=> $row['title'],
					'album_description'	=> $row['description'],
					'album_type'		=> 1, // public albums only
					'album_category_id'	=> $parent_id,
					'album_owner_id'	=> $member_id,
                    'album_allow_comments' => 1,
					);

//                $message = "" . $parent_id . "<br>";
//                $this->registry->output->html .= $message;

                $this->lib->convertAlbum( $row['id'], $save, $us, $skip_cat_link);
            }

			$this->lib->next();

		}

		/**
		 * Convert Images
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_gallery_images()
		{

			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$this->lib->saveMoreInfo('gallery_images', array('gallery_path'));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'items',
                            'where'     => "type='photo'",
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('gallery_images', $main);

			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------

			$this->lib->getMoreInfo('gallery_images', $loop, array('gallery_path' => array('type' => 'text', 'label' => 'The path to the folder where images are saved (no trailing slash - usually path_to_gallery3/var/albums):')), 'path');

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$path = $us['gallery_path'];

			//-----------------------------------------
			// Check all is well
			//-----------------------------------------

			if (!is_writable($this->settings['gallery_images_path']))
			{
				$this->lib->error('Your IP.Gallery upload path is not writeable. '.$this->settings['gallery_images_path']);
			}
			if (!is_readable($path))
			{
				$this->lib->error('Your remote upload path is not readable.');
			}

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// Do the image
				//-----------------------------------------

				// Basic info
				$save = array(
					'image_member_id'				=>	$row['owner_id'],
					'image_album_id'				=>	$row['parent_id'],
					'image_caption'					=>	$row['title'],
					'image_description'				=>	$row['description'],
					//'image_directory'				=>	$row['relative_path_cache'],
					'image_masked_file_name'		=>	urldecode( $row['relative_path_cache'] ),
					'image_file_name'				=>	$row['name'],
					'image_file_size'				=>	$row['filesize'],
					'image_file_type'				=>	$row['mime_type'],
					'image_approved'				=>	1,
					'image_views'					=>	$row['view_count'],
					'image_comments'				=>	$this->lib->countRows('comments', "item_id={$row['id']}"),
					'image_date'					=>	$row['created'],
					'image_ratings_total'			=>	0,
					'image_ratings_count'			=>	0,
					'image_rating'					=>	0,
					);

				// Go!
				$this->lib->convertImage($row['id'], $save, $path);

			}

			$this->lib->next();

		}

		/**
		 * Convert Comments
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_gallery_comments()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'comments',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('gallery_comments', $main);


			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
                $author_name = "";
                $text = "";
                if($row['author_id'] > 1)
                {
                    $member = IPSMember::load( intval( $row['author_id'] ) );
                    $author_name = $member['name'];

                    $text = nl2br($row['text']);
                }
                else
                {
                    $author_name = 'admin';

                    // if guest-name was set, add it to the comment-text
                    /*
                        guest_name
                        ----------------------

                        Acheros
                        Anas
                        Anonymer Benutzer
                        Anonymous coward
                        Aranoel
                        Barahir
                        Ei
                        Gumble
                        Lú
                        Merry
                        Thuni
                        Tomtom
                        urubaxi
                        vasi
                        wie siht denn dass aus

                    */
                    $text = $row['guest_name'] . ":<br />" . nl2br($row['text']);
                }

                $save = array(
					'comment_img_id'			=> $row['item_id'],
					'comment_author_name'		=> $author_name,
					'comment_text'			    => $text,
					'comment_post_date'			=> $row['created'],
					'comment_ip_address'		=> $row['server_http_host'],
					'comment_author_id'			=> $row['author_id'],
					'comment_approved'			=> 1,
					);

				$this->lib->convertComment($row['id'], $save);
			}

			$this->lib->next();

		}

	}

