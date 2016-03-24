<?php

class SiteMeta
{
    private $CI;
    private $site_params;
    
    function __construct(){
        $this->CI =&get_instance();
        
    }
    
    private function _general($title=null, $metas=array(), $schemes=array()){
        $tx = '<meta charset="utf-8">';
        $tx.= '<meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">';
        if($this->CI->setting->item('site_theme_responsive'))
            $tx.= '<meta content="width=device-width, minimum-scale=1, maximum-scale=1, user-scalable=no" name="viewport">';
        $tx.= '<meta content="AdminCI" name="generator">';
        
        $google_code = $this->CI->setting->item('code_verification_google');
        if($google_code)
            $tx.= '<meta name="google-site-verification" content="' . $google_code . '">';
        
        $alexa_code = $this->CI->setting->item('code_verification_alexa');
        if($alexa_code)
            $tx.= '<meta name="alexaVerifyID" content="' . $alexa_code . '">';
        
        $bing_code = $this->CI->setting->item('code_verification_bing');
        if($bing_code)
            $tx.= '<meta name="msvalidate.01" content="' . $bing_code . '">';
        
        $pinterest_code = $this->CI->setting->item('code_verification_pinterest');
        if($pinterest_code)
            $tx.= '<meta name="p:domain_verify" content="' . $pinterest_code . '">';
        
        $yandex_code = $this->CI->setting->item('code_verification_yandex');
        if($yandex_code)
            $tx.= '<meta name="yandex-verification" content="' . $yandex_code . '">';
        
        $facebook_code = $this->CI->setting->item('code_application_facebook');
        if($facebook_code)
            $tx.= '<meta content="' . $facebook_code . '" property="fb:app_id">';
        
        $tx.= '<meta property="og:site_name" content="' . $this->CI->setting->item('site_name') . '">';
        
        // additional metas
        $prop_or_name = array(
            'keywords' => 'name',
            'description' => 'name',
            'twitter:card' => 'name',
            'twitter:description' => 'name',
            'twitter:image:src' => 'name',
            'twitter:title' => 'name',
            'twitter:url' => 'name',
            'og:description' => 'property',
            'og:image' => 'property',
            'og:title' => 'property',
            'og:type' => 'property',
            'og:url' => 'property',
            'article:published_time' => 'property',
            'article:section' => 'property',
            'article:tag' => 'property',
            'profile:username' => 'property',
            'profile:first_name' => 'property',
            'profile:last_name' => 'property'
        );
        foreach($metas as $name => $mets){
            $prop = 'name';
            if(array_key_exists($name, $prop_or_name))
                $prop = $prop_or_name[$name];
            if(is_array($mets)){
                foreach($mets as $met)
                    $tx.= '<meta ' . $prop . '="' . $name . '" content="' . $met . '">';
            }else{
                $tx.= '<meta ' . $prop . '="' . $name . '" content="' . $mets . '">';
            }
        }
        
        $tx.= '<link href="' . $this->CI->theme->asset('/static/image/logo/shortcut-icon.png') . '" rel="shortcut icon">';
        $tx.= '<link href="' . $this->CI->theme->asset('/static/image/logo/apple-touch-icon.png') . '" rel="apple-touch-icon">';
        $tx.= '<link href="' . $this->CI->theme->asset('/static/image/logo/apple-touch-icon-72x72.png') . '" rel="apple-touch-icon" sizes="72x72">';
        $tx.= '<link href="' . $this->CI->theme->asset('/static/image/logo/apple-touch-icon-114x114.png') . '" rel="apple-touch-icon" sizes="114x114">';
        
        $tx.= '<link href="' . base_url('feed.xml') . '" rel="alternate" title="' . $this->CI->setting->item('site_frontpage_title') . '" type="application/rss+xml">';
        
        $tx.= '<link href="' . base_url(uri_string()) . '" rel="canonical">';
        
        $ga_code = $this->CI->setting->item('code_google_analytics');
        if($ga_code)
            $tx.= '<script>(function(i,s,o,g,r,a,m){i[\'GoogleAnalyticsObject\']=r;i[r]=i[r]||function(){ (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o), m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m) })(window,document,\'script\',\'//www.google-analytics.com/analytics.js\',\'ga\'); ga(\'create\', \'' . $ga_code . '\', \'auto\'); ga(\'send\', \'pageview\');</script>';
        
        if($title)
            $tx.= '<title>' . $title . ' - ' . $this->CI->setting->item('site_name') . '</title>';
        
        $data = array(
            '@context'      => 'http://schema.org',
            '@type'         => 'Website',
            'url'           => base_url(),
            'potentialAction' => array(
                '@type'         => 'SearchAction',
                'target'        => base_url('/post/search') . '?q={search_term_string}',
                'query-input'   => 'required name=search_term_string'
            )
        );
        
        $tx.= '<script type="application/ld+json">';
        $tx.= json_encode($data, JSON_UNESCAPED_SLASHES);
        $tx.= '</script>';
        foreach($schemes as $scheme){
            $tx.= '<script type="application/ld+json">';
            $tx.= json_encode($scheme, JSON_UNESCAPED_SLASHES);
            $tx.= '</script>';
        }
        
        return $tx;
    }
    
    private function _schemaBreadcrumb($links){
        $data = array(
            '@context'  => 'http://schema.org',
            '@type'     => 'BreadcrumbList',
            'itemListElement' => array()
        );
        
        $index = 1;
        foreach($links as $id => $name){
            $data['itemListElement'][] = array(
                '@type' => 'ListItem',
                    'position' => $index,
                    'item' => array(
                        '@id' => $id,
                        'name' => $name
                    )
            );
            $index++;
        }
        
        return $data;
    }
    
    public function gallery_single($gallery){
        $meta_title = $gallery->seo_title;
        if(!$meta_title)
            $meta_title = $gallery->title;
        
        $meta_description = $gallery->seo_description;
        if(!$meta_description)
            $meta_description = $gallery->description->chars(160);
        
        $meta_keywords = $gallery->seo_keywords;
        $meta_image = $gallery->cover;
        $meta_url   = base_url($gallery->page);
        
        $metas = array(
            "description"           => $meta_description,
            "keywords"              => $meta_keywords,
            "twitter:card"          => "summary_large_image",
            "twitter:description"   => $meta_description,
            "twitter:image:src"     => $meta_image,
            "twitter:title"         => $meta_title,
            "twitter:url"           => $meta_url,
            "og:description"        => $meta_description,
            "og:image"              => $meta_image,
            "og:title"              => $meta_title,
            "og:type"               => "website",
            "og:url"                => $meta_url
        );
        
        $schemas = array();
        if($gallery->seo_schema->value){
            $schemas[] = array(
                '@context'      => 'http://schema.org',
                '@type'         => $gallery->seo_schema,
                'name'          => $meta_title,
                'description'   => $meta_description,
                'image'         => $meta_image,
                'url'           => $meta_url,
                'keywords'      => $gallery->seo_keywords,
                'datePublished' => $gallery->created->format('c'),
                'dateCreated'   => $gallery->created->format('c')
            );
        }
        
        $schemas[] = $this->_schemaBreadcrumb([
            base_url() => $this->CI->setting->item('site_name'),
            base_url('/gallery') => _l('Gallery')
        ]);
        
        echo $this->_general($meta_title, $metas, $schemas);
    }
    
    public function home($title=null){
        if(!$title)
            $title = $this->CI->setting->item('site_frontpage_title');
        
        $meta_description = $this->CI->setting->item('site_frontpage_description');
        $meta_name  = $this->CI->setting->item('site_name');
        $meta_image = $this->CI->theme->asset('/static/image/logo/logo.png');
        $meta_keywords = $this->CI->setting->item('site_frontpage_keywords');
        
        $metas = array(
            "description"           => $meta_description,
            "keywords"              => $meta_keywords,
            "twitter:card"          => "summary_large_image",
            "twitter:description"   => $meta_description,
            "twitter:image:src"     => $meta_image,
            "twitter:title"         => $meta_name,
            "twitter:url"           => base_url(),
            "og:description"        => $meta_description,
            "og:image"              => $meta_image,
            "og:title"              => $meta_name,
            "og:type"               => "website",
            "og:url"                => base_url()
        );
        
        $schemas = array();
        
        $data = array(
            '@context'      => 'http://schema.org',
            '@type'         => 'Organization',
            'name'          => $this->CI->setting->item('site_name'),
            'url'           => base_url(),
            'logo'          => $this->CI->theme->asset('/static/image/logo/logo.png')
        );
        
        // social url
        $socials = array();
        $known_socials = array(
            'facebook',
            'gplus',
            'instagram',
            'linkedin',
            'myspace',
            'pinterest',
            'soundcloud',
            'tumblr',
            'twitter',
            'youtube'
        );
        foreach($known_socials as $soc){
            $url = $this->CI->setting->item('site_x_social_'.$soc);
            if($url)
                $socials[] = $url;
        }
        
        if($socials)
            $data['sameAs'] = $socials;
        
        // phone contact number
        $contacts = array();
        $known_contacts = array(
            'customer_support' => 'customer support',
            'technical_support' => 'technical support',
            'billing_support' => 'billing support',
            'bill_payment' => 'bill payment',
            'sales' => 'sales',
            'reservations' => 'reservations',
            'credit_card_support' => 'credit card support',
            'emergency' => 'emergency',
            'baggage_tracking' => 'baggage tracking',
            'roadside_assistance' => 'roadside assistance',
            'package_tracking' => 'package tracking'
        );
        $contact_served = $this->CI->setting->item('organization_contact_area_served');
        if($contact_served){
            $contact_served = explode(',', $contact_served);
            if(count($contact_served) == 1)
                $contact_served = $contact_served[0];
        }
        
        $contact_language = $this->CI->setting->item('organization_contact_available_language');
        if($contact_language){
            $contact_language = explode(',', $contact_language);
            if(count($contact_language) == 1)
                $contact_language = $contact_language[0];
        }
        
        $contact_options = array();
        if($this->CI->setting->item('organization_contact_opt_tollfree'))
            $contact_options[] = 'TollFree';
        if($this->CI->setting->item('organization_contact_opt_his'))
            $contact_options[] = 'HearingImpairedSupported';
        
        foreach($known_contacts as $cont => $name){
            $phone = $this->CI->setting->item('organization_contact_' . $cont);
            if(!$phone)
                continue;
            $contact = array(
                '@type' => 'ContactPoint',
                'telephone' => $phone,
                'contactType' => $name
            );
            if($contact_served)
                $contact['areaServed'] = $contact_served;
            if($contact_language)
                $contact['availableLanguage'] = $contact_language;
            if($contact_options)
                $contact['contactOption'] = $contact_options;
            $contacts[] = $contact;
        }
        
        if($contacts)
            $data['contactPoint'] == $contacts;
        
        $schemas[] = $data;
        
        echo $this->_general($title, $metas, $schemas);
    }
    
    public function page_single($page){
        $meta_title = $page->seo_title;
        if(!$meta_title)
            $meta_title = $page->title;
        
        $meta_description = $page->seo_description;
        if(!$meta_description)
            $meta_description = $page->content->chars(160);
        
        $meta_keywords = $page->seo_keywords;
        $meta_image = $this->CI->theme->asset('/static/image/logo/logo.png');
        $meta_name  = $this->CI->setting->item('site_name');
        $meta_url   = base_url($page->page);
        
        $metas = array(
            "description"           => $meta_description,
            "keywords"              => $meta_keywords,
            "twitter:card"          => "summary_large_image",
            "twitter:description"   => $meta_description,
            "twitter:image:src"     => $meta_image,
            "twitter:title"         => $meta_title,
            "twitter:url"           => $meta_url,
            "og:description"        => $meta_description,
            "og:image"              => $meta_image,
            "og:title"              => $meta_title,
            "og:type"               => "website",
            "og:url"                => $meta_url
        );
        
        $schemas = array();
        
        if($page->seo_schema->value){
            $schemas[] = array(
                '@context'      => 'http://schema.org',
                '@type'         => $page->seo_schema,
                'name'          => $meta_title,
                'description'   => $meta_description,
                'url'           => base_url($page->page),
                'dateCreated'   => $page->created->format('c')
            );
        }
        
        $schemas[] = $this->_schemaBreadcrumb([
            base_url() => $meta_name,
            base_url('/page') => _l('Page')
        ]);
        
        echo $this->_general($meta_title, $metas, $schemas);
    }
    
    public function post_category_single($category){
        $meta_title = $category->seo_title;
        if(!$meta_title)
            $meta_title = $category->name;
        
        $meta_description = $category->seo_description;
        if(!$meta_description)
            $meta_description = $category->content->chars(160);
        
        $meta_keywords = $category->seo_keywords;
        $meta_image = $this->CI->theme->asset('/static/image/logo/logo.png');
        $meta_name  = $this->CI->setting->item('site_name');
        $meta_url   = base_url($category->page);
        
        $metas = array(
            "description"           => $meta_description,
            "keywords"              => $meta_keywords,
            "twitter:card"          => "summary_large_image",
            "twitter:description"   => $meta_description,
            "twitter:image:src"     => $meta_image,
            "twitter:title"         => $meta_title,
            "twitter:url"           => $meta_url,
            "og:description"        => $meta_description,
            "og:image"              => $meta_image,
            "og:title"              => $meta_title,
            "og:type"               => "website",
            "og:url"                => $meta_url
        );
        
        $schemas = array();
        
        if($category->seo_schema->value){
            $schemas[] = array(
                '@context'      => 'http://schema.org',
                '@type'         => $category->seo_schema,
                'name'          => $meta_title,
                'description'   => $meta_description,
                'image'         => $meta_image,
                'url'           => $meta_url,
                'keywords'      => $category->seo_keywords,
                'datePublished' => $category->created->format('c'),
                'dateCreated'   => $category->created->format('c')
            );
        }
        
        $schemas[] = $this->_schemaBreadcrumb([
            base_url() => $meta_name,
            base_url('/post') => _l('Post'),
            base_url('/post/category') => _l('Category')
        ]);
        
        echo $this->_general($meta_title, $metas, $schemas);
    }
    
    public function post_single($post){
        $meta_title = $post->seo_title;
        if(!$meta_title)
            $meta_title = $post->title;
        
        $meta_description = $post->seo_description;
        if(!$meta_description)
            $meta_description = $post->content->chars(160);
        
        $meta_keywords = $post->seo_keywords;
        $meta_image = $post->cover;
        $meta_name  = $this->CI->setting->item('site_name');
        $meta_url   = base_url($post->page);
        $schemas    = array();
        $schema_bread = [
            base_url() => $meta_name,
            base_url('/post') => _l('Post')
        ];
        
        $metas = array(
            "description"           => $meta_description,
            "keywords"              => $meta_keywords,
            "twitter:card"          => "summary_large_image",
            "twitter:description"   => $meta_description,
            "twitter:image:src"     => $meta_image,
            "twitter:title"         => $meta_title,
            "twitter:url"           => $meta_url,
            "og:description"        => $meta_description,
            "og:image"              => $meta_image,
            "og:title"              => $meta_title,
            "og:type"               => "article",
            "og:url"                => $meta_url,
            "article:published_time"=> $post->published->format('c')
        );
        
        if(property_exists($post, 'category')){
            foreach($post->category as $cat){
                $metas["article:section"] = $cat->name;
                $schema_bread[base_url($cat->page)] = $cat->name;
                break;
            }
        }
        if(property_exists($post, 'tag')){
            $metas['article:tag'] = array();
            foreach($post->tag as $tag)
                $metas['article:tag'][] = $tag->name;
        }
        
        if(!$post->seo_schema->value)
            $post->seo_schema = 'Article';
        
        // fuck get image sizes
        $image_file = dirname(BASEPATH) . $meta_image->value;
        if(is_file($image_file)){
            list($img_width, $img_height) = getimagesize($image_file);
            
            $schemas[] = array(
                '@context'      => 'http://schema.org',
                '@type'         => $post->seo_schema,
                'name'          => $meta_title,
                'description'   => $meta_description,
                'author'        => array(
                    '@type'         => 'Person',
                    'name'          => $post->user->fullname,
                    'url'           => base_url($post->user->page)
                ),
                'image'         => array(
                    '@type'         => 'ImageObject',
                    'url'           => $meta_image,
                    'height'        => $img_height,
                    'width'         => $img_width
                ),
                'headline'      => $meta_title,
                'url'           => $meta_url,
                'keywords'      => $meta_keywords,
                'mainEntityOfPage' => array(
                    '@type'         => 'WebPage',
                    '@id'           => $meta_url
                ),
                'publisher'     => array(
                    '@type'         => 'Organization',
                    'name'          => $meta_name,
                    'logo'          => array(
                        '@type'         => 'ImageObject',
                        'url'           => $this->CI->theme->asset('/static/image/logo/logo-200x60.png'),
                        'width'         => 200,
                        'height'        => 60
                    )
                ),
                'datePublished' => $post->published->format('c'),
                'dateModified'  => $post->published->format('c'),
                'dateCreated'   => $post->created->format('c')
            );
        }
        
        $schemas[] = $this->_schemaBreadcrumb($schema_bread);
        
        echo $this->_general($meta_title, $metas, $schemas);
    }
    
    public function post_tag_single($tag){
        $meta_title = $tag->seo_title;
        if(!$meta_title)
            $meta_title = $tag->name;
        
        $meta_description = $tag->seo_description;
        if(!$meta_description)
            $meta_description = $tag->description->chars(160);
        
        $meta_keywords = $tag->seo_keywords;
        $meta_image = $this->CI->theme->asset('/static/image/logo/logo.png');
        $meta_name  = $this->CI->setting->item('site_name');
        $meta_url   = base_url($tag->page);
        
        $metas = array(
            "description"           => $meta_description,
            "keywords"              => $meta_keywords,
            "twitter:card"          => "summary_large_image",
            "twitter:description"   => $meta_description,
            "twitter:image:src"     => $meta_image,
            "twitter:title"         => $meta_title,
            "twitter:url"           => $meta_url,
            "og:description"        => $meta_description,
            "og:image"              => $meta_image,
            "og:title"              => $meta_title,
            "og:type"               => "website",
            "og:url"                => $meta_url
        );
        
        if($tag->seo_schema->value){
            $schemas[] = array(
                '@context'      => 'http://schema.org',
                '@type'         => $tag->seo_schema,
                'name'          => $meta_title,
                'description'   => $meta_description,
                'image'         => $meta_image,
                'url'           => $meta_url,
                'keywords'      => $meta_keywords,
                'datePublished' => $tag->created->format('c'),
                'dateCreated'   => $tag->created->format('c')
            );
        }
        
        $schemas[] = $this->_schemaBreadcrumb([
            base_url() => $meta_name,
            base_url('/post') => _l('Post'),
            base_url('/post/tag') => _l('Tag')
        ]);
        
        echo $this->_general($meta_title, $metas, $schemas);
    }
    
    public function user_single($user){
        $meta_title = $user->fullname;
        
        $meta_description = $user->about->chars(160);
        $meta_keywords = '';
        $meta_image = $user->avatar;
        $meta_name  = $this->CI->setting->item('site_name');
        $meta_url   = base_url($user->page);
        
        $metas = array(
            "description"           => $meta_description,
            "keywords"              => $meta_keywords,
            "twitter:card"          => "summary_large_image",
            "twitter:description"   => $meta_description,
            "twitter:image:src"     => $meta_image,
            "twitter:title"         => $meta_title,
            "twitter:url"           => $meta_url,
            "og:description"        => $meta_description,
            "og:image"              => $meta_image,
            "og:title"              => $meta_title,
            "og:type"               => "profile",
            "og:url"                => $meta_url,
            "profile:username"      => $user->name,
            
        );
        
        $fname = explode(' ', $user->fullname);
        if($fname[0])
            $metas["profile:first_name"] = $fname[0];
        if(array_key_exists(1, $fname) && $fname[1])
            $metas["profile:last_name"] = $fname[1];
            
        $schemas[] = array(
            '@context'      => 'http://schema.org',
            '@type'         => 'Person',
            'email'         => $user->email,
            'image'         => $user->avatar,
            'name'          => $user->fullname,
            'url'           => $meta_url
        );
        
        // schema breadcrumb
        $schemas[] = $this->_schemaBreadcrumb([
            base_url() => $meta_name,
            base_url('/user') => _l('User')
        ]);
        
        echo $this->_general($meta_title, $metas, $schemas);
    }
}