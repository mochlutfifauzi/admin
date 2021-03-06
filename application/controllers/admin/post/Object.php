<?php

if(!defined('BASEPATH'))
    die;

/**
 * The `Object` controller
 */
class Object extends MY_Controller
{

    function __construct(){
        parent::__construct();

        $this->load->model('Post_model', 'Post');
        $this->load->library('ObjectFormatter', '', 'formatter');
    }

    function edit($id=null){
        if(!$this->user)
            return $this->redirect('/admin/me/login?next=' . uri_string());
        if(!$id && !$this->can_i('create-post'))
            return $this->show_404();
        if($id && !$this->can_i('update-post'))
            return $this->show_404();

        $this->load->library('SiteForm', '', 'form');

        $this->load->model('Postcategory_model', 'PCategory');
        $this->load->model('Postcategorychain_model', 'PCChain');
        $this->load->model('Posttag_model', 'PTag');
        $this->load->model('Posttagchain_model', 'PTChain');
        $this->load->model('Gallery_model', 'Gallery');
        $this->load->model('Postschedule_model', 'PSchedule');
        
        $post_scheduled = false;
        
        $params = array(
            'slug_editable' => true,
            'reporter' => null
        );
        
        if($id){
            $object = $this->Post->get($id);
            if(!$object)
                return $this->show_404();
            
            // allow user to edit other user posts only if he's allowed to do so.
            if($object->user != $this->user->id && !$this->can_i('update-post_other_user'))
                return $this->show_404();
            
            $params['title'] = _l('Edit Post');
            
            $object_categories = $this->PCChain->getBy('post', $id, true);
            $object->category = $object_categories ? prop_values($object_categories, 'post_category') : array();
            $object_tags = $this->PTChain->getBy('post', $id, true);
            $object->tag = $object_tags ? prop_values($object_tags, 'post_tag') : array();
            
            if(!$this->can_i('update-post_slug'))
                $params['slug_editable'] = false;
            
            if($object->user != $this->user->id){
                $reporter = $this->User->get($object->user);
                if($reporter)
                    $params['reporter'] = $this->formatter->user($reporter);
            }
        }else{
            $object = (object)array('status' => 1);
            if($this->can_i('read-post_category'))
                $object->category = [];
            if($this->can_i('read-post_tag'))
                $object->tag = [];
            
            $params['title'] = _l('Create New Post');
        }

        $this->form->setObject($object);
        $this->form->setForm('/admin/post');

        $params['post'] = $object;
        
        if($this->can_i('read-post_category')){
            $all_categories = array();
            
            $categories = $this->PCategory->getByCond([], true, false, ['name'=>'ASC']);
            if($categories){
                $all_categories = $this->formatter->post_category($categories, 'id', false);
                $categories = group_by_prop($categories, 'parent');
            }
            $params['categories'] = $categories ? $categories : array();
        }
        
        if($this->can_i('read-post_tag')){
            $all_tags = array();
            $params['tags'] = array();
            
            $tags = $this->PTag->getByCond([], true, false, ['name'=>'ASC']);
            if($tags){
                $all_tags = $this->formatter->post_tag($tags, 'id', false);
                $params['tags'] = prop_as_key($tags, 'id', 'name');
                
                // let show only all selected tag
                $visible_tag = array();
                if($object->tag){
                    foreach($object->tag as $tag){
                        if(array_key_exists($tag, $params['tags']))
                            $visible_tag[$tag] = $params['tags'][$tag];
                    }
                }
                
                $params['tags'] = $visible_tag;
            }
        }
        
        if($this->can_i('read-gallery')){
            $params['galleries'] = array();
            if(property_exists($object, 'gallery') && $object->gallery){
                $gallery = $this->Gallery->get($object->gallery);
                if($gallery)
                    $params['galleries'] = array( $object->gallery => $gallery->name );
            }else{
                $galleries = $this->Gallery->getByCond([], 10);
                if($galleries)
                    $params['galleries'] = prop_as_key($galleries, 'id', 'name');
            }
        }
        
        $statuses = $this->enum->item('post.status');
        if(!$this->can_i('create-post_published')){
            unset($statuses[4]);
            unset($statuses[3]);
        }
        $params['statuses'] = $statuses;
        
        if(!($new_object=$this->form->validate($object)))
            return $this->respond('post/edit', $params);
        
        if($new_object === true)
            return $this->redirect('/admin/post');
        
        // remove instant article
        if(array_key_exists('content', $new_object))
            $new_object['instant_content'] = NULL;
        
        // make sure user not publish it if user not allowed to publish it
        // or set the published property if it's published
        if(array_key_exists('status', $new_object)){
            if(in_array($new_object['status'], [3,4])){
                if($this->can_i('create-post_published')){
                    if($new_object['status'] == 4){
                        $new_object['published'] = date('Y-m-d H:i:s');
                        $new_object['publisher'] = $this->user->id;
                        
                    // add the post to post_schedule to be listed on
                    // publish later post
                    }else{
                        $post_scheduled = $new_object['published'];
                        $new_object['publisher'] = $this->user->id;
                    }
                    if($id)
                        $this->PSchedule->removeBy('post', $id);
                }else{
                    unset($new_object['status']);
                    if(array_key_exists('published', $new_object))
                        unset($new_object['published']);
                }
            }
        }elseif(array_key_exists('published', $new_object) && $object->status == 3){
            if($this->can_i('create-post_published')){
                $post_scheduled = $new_object['published'];
                if($id)
                    $this->PSchedule->removeBy('post', $id);
            }else{
                unset($new_object['published']);
            }
        }
        
        // make sure user not change the slug if he's not allowed
        if($id && array_key_exists('slug', $new_object) && !$this->can_i('update-post_slug'))
            unset($new_object['slug']);
        
        // save category chain
        $to_insert_category = array();
        if(!array_key_exists('category', $new_object))
            $new_object['category'] = array();
        
        $new_categories = $new_object['category'];
        unset($new_object['category']);
        
        if($this->can_i('read-post_category')){
            
            $old_categories = array();
            if($id)
                $old_categories = $object->category;
            
            $to_insert = array();
            $to_delete = array();
            
            foreach($new_categories as $cat){
                if(!in_array($cat, $old_categories)){
                    $category = null;
                    if(array_key_exists($cat, $all_categories))
                        $category = $all_categories[$cat];
                    if(!$category)
                        continue;
                    
                    $to_insert[] = $cat;
                    $this->PCategory->inc($cat, 'posts', 1, true);
                    $this->output->delete_cache($category->page);
                    $this->output->delete_cache($category->page . '/feed.xml');
                }
            }
            
            foreach($old_categories as $cat){
                $old_category = null;
                if(array_key_exists($cat, $all_categories))
                    $old_category = $all_categories[$cat];
                    
                if(!in_array($cat, $new_categories)){
                    $category = null;
                    if(array_key_exists($cat, $all_categories))
                        $category = $all_categories[$cat];
                    if(!$category)
                        continue;
                    
                    $to_delete[] = $cat;
                    $this->PCategory->dec($cat, 'posts', 1, true);
                }
                
                // delete all post category cache
                if($old_category){
                    $this->output->delete_cache($old_category->page);
                    $this->output->delete_cache($old_category->page . '/feed.xml');
                }
            }
            
            if($to_delete)
                $this->PCChain->removeByCond(['post'=>$id, 'post_category'=>$to_delete]);

            if($to_insert)
                $to_insert_category = $to_insert;
        }
        
        // save tag chain
        $to_insert_tag = array();
        if(!array_key_exists('tag', $new_object))
            $new_object['tag'] = array();
        
        $new_tags = $new_object['tag'];
        unset($new_object['tag']);
        
        if($this->can_i('read-post_tag')){
        
            $old_tags = array();
            if($id)
                $old_tags = $object->tag;
            
            $to_insert = array();
            $to_delete = array();
            
            foreach($new_tags as $cat){
                if(!in_array($cat, $old_tags)){
                    $tag = null;
                    if(array_key_exists($cat, $all_tags))
                        $tag = $all_tags[$cat];
                    if(!$tag)
                        continue;
                    
                    $to_insert[] = $cat;
                    $this->PTag->inc($cat, 'posts', 1, true);
                    $this->output->delete_cache($tag->page);
                    $this->output->delete_cache($tag->page . '/feed.xml');
                }
            }
            
            foreach($old_tags as $cat){
                $old_tag = null;
                if(array_key_exists($cat, $all_tags))
                    $old_tag = $all_tags[$cat];
                if(!in_array($cat, $new_tags)){
                    $tag = null;
                    if(array_key_exists($cat, $all_tags))
                        $tag = $all_tags[$cat];
                    if(!$tag)
                        continue;
                    
                    $to_delete[] = $cat;
                    $this->PTag->dec($cat, 'posts', 1, true);
                }
                if($old_tag){
                    $this->output->delete_cache($old_tag->page);
                    $this->output->delete_cache($old_tag->page . '/feed.xml');
                }
            }
            
            if($to_delete)
                $this->PTChain->removeByCond(['post'=>$id, 'post_tag'=>$to_delete]);

            if($to_insert)
                $to_insert_tag = $to_insert;
        }
        
        $this->output->delete_cache('/post/feed.xml');
        $this->output->delete_cache('/post/instant.xml');
        
        if($id){
            $fobject = $this->formatter->post($object, false, false);
            $this->output->delete_cache($fobject->page);
            $this->output->delete_cache($fobject->amp);
        }
        
        if($new_object){
            $new_object['updated'] = date('Y-m-d H:i:s');
            if(!$id){
                $new_object['user'] = $this->user->id;
                $new_object['id'] = $this->Post->create($new_object);
                $id = $new_object['id'];
                
                $this->event->post->created($new_object);
            }else{
                $this->Post->set($id, $new_object);
                
                $this->event->post->updated($object, $new_object);
            }
            
            if($post_scheduled){
                $post_scheduled = (object)array(
                    'post' => $id,
                    'published' => $post_scheduled
                );
                $this->PSchedule->removeBy('post', $post_scheduled->post);
                $this->PSchedule->create($post_scheduled);
            }
        }
        
        if($to_insert_tag && $id){
            foreach($to_insert_tag as $index => $tag)
                $to_insert_tag[$index] = ['post'=>$id, 'post_tag'=>$tag];
            $this->PTChain->create_batch($to_insert_tag);
        }
        
        if($to_insert_category && $id){
            foreach($to_insert_category as $index => $cat)
                $to_insert_category[$index] = ['post'=>$id, 'post_category'=>$cat];
            $this->PCChain->create_batch($to_insert_category);
        }
        
        $this->redirect('/admin/post');
    }

    function index(){
        if(!$this->user)
            return $this->redirect('/admin/me/login?next=' . uri_string());
        if(!$this->can_i('read-post'))
            return $this->show_404();

        $params = array(
            'title' => _l('Posts'),
            'posts' => [],
            'categories' => array(),
            'tag' => null,
            'statuses' => $this->enum->item('post.status'),
            'pagination' => array(),
            'user' => null
        );

        $cond = array();

        $args = ['q','tag','category','status','user'];
        foreach($args as $arg){
            $arg_val = $this->input->get($arg);
            if($arg_val)
                $cond[$arg] = $arg_val;
        }
        
        if(!$this->can_i('read-post_other_user'))
            $cond['user'] = $this->user->id;
        elseif(array_key_exists('user', $cond))
            $params['user'] = $this->User->get($cond['user']);
        
        if(array_key_exists('tag', $cond)){
            $this->load->model('Posttag_model', 'PTag');
            $params['tag'] = $this->PTag->get($cond['tag']);
        }
        
        if($this->can_i('read-post_category')){
            $this->load->model('Postcategory_model', 'PCategory');
            $all_categories = $this->PCategory->getByCond([], true);
            $params['categories'] = $all_categories;
        }
        
        $rpp = 20;
        $page= $this->input->get('page');
        if(!$page)
            $page = 1;

        $result = $this->Post->findByCond($cond, $rpp, $page, ['updated'=>'DESC', 'published'=>'DESC']);
        if($result)
            $params['posts'] = $this->formatter->post($result, false, false);

        $total_result = $this->Post->findByCondTotal($cond);
        if($total_result > $rpp){
            $pagination_cond = $cond;
            if(array_key_exists('q', $cond))
                $pagination_cond['q'] = $cond['q'];
            
            $this->load->helper('pagination');
            $params['pagination'] = calculate_pagination($total_result, $page, $rpp, $pagination_cond);
        }
        
        $this->respond('post/index', $params);
    }

    function remove($id){
        if(!$this->user)
            return $this->redirect('/admin/me/login?next=' . uri_string());
        if(!$this->can_i('delete-post'))
            return $this->show_404();
        
        $post = $this->Post->get($id);
        if(!$post)
            return $this->show_404();

        if($post->user != $this->user->id && !$this->can_i('delete-post_other_user'))
            return $this->show_404();
        
        $this->load->model('Postcategorychain_model', 'PCChain');
        $this->load->model('Postcategory_model', 'PCategory');
        $this->load->model('Posttagchain_model', 'PTChain');
        $this->load->model('Posttag_model', 'PTag');
        $this->load->model('Postselection_model', 'PSelection');
        
        $this->Post->remove($id);
        
        // remove post category chain and dec total posts of the category
        $cats_chain = $this->PCChain->getBy('post', $id, true);
        if($cats_chain){
            $cats_chain_id = array();
            $cats_id = prop_values($cats_chain, 'post_category');
            $cats = $this->PCategory->get($cats_id, true);
            $cats = $this->formatter->post_category($cats, 'id', false);
            foreach($cats_chain as $cat_chain){
                $cats_chain_id[] = $cat_chain->id;
                if(!array_key_exists($cat_chain->post_category, $cats))
                    continue;
                $cat = $cats[$cat_chain->post_category];
                $this->PCategory->dec($cat->id, 'posts', 1, true);
                $this->output->delete_cache($cat->page);
                $this->output->delete_cache($cat->page . '/feed.xml');
            }
            
            $this->PCChain->remove($cats_chain_id);
        }
        
        // remove post tag chain and dec total posts of the tag
        $tags_chain = $this->PTChain->getBy('post', $id, true);
        if($tags_chain){
            $tags_chain_id = array();
            $tags_id = prop_values($tags_chain, 'post_tag');
            $tags = $this->PTag->get($tags_id, true);
            $tags = $this->formatter->post_tag($tags, 'id', false);
            foreach($tags_chain as $tag_chain){
                $tags_chain_id[] = $tag_chain->id;
                if(!array_key_exists($tag_chain->post_tag, $tags))
                    continue;
                $tag = $tags[$tag_chain->post_tag];
                $this->PTag->dec($tag->id, 'posts', 1, true);
                $this->output->delete_cache($tag->page);
                $this->output->delete_cache($tag->page . '/feed.xml');
            }
            
            $this->PTChain->remove($tags_chain_id);
        }
        
        // remove post selection
        $post_selection = $this->PSelection->getBy('post', $post->id, true);
        if($post_selection){
            $this->cache->file->delete('post_selection');
            $this->PSelection->removeBy('post', $post->id);
        }
        
        $this->event->post->deleted($post);
        
        $post = $this->formatter->post($post, false, false);
        
        $this->output->delete_cache($post->page);
        $this->output->delete_cache($post->amp);
        $this->output->delete_cache('/post/feed.xml');
        $this->output->delete_cache('/post/instant.xml');
        
        $this->redirect('/admin/post');
    }
}