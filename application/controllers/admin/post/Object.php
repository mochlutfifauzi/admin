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
        
        $params = array(
            'slug_editable' => true
        );
        
        if($id){
            $object = $this->Post->get($id);
            if(!$object)
                return $this->show_404();
            $params['title'] = _l('Edit Post');
            
            $object_categories = $this->PCChain->getBy('post', $id, true);
            $object->category = $object_categories ? prop_values($object_categories, 'post_category') : array();
            $object_tags = $this->PTChain->getBy('post', $id, true);
            $object->tag = $object_tags ? prop_values($object_tags, 'post_tag') : array();
            
            if(!$this->can_i('update-post_slug'))
                $params['slug_editable'] = false;
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
                $all_categories = prop_as_key($categories, 'id');
                $categories = group_by_prop($categories, 'parent');
            }
            $params['categories'] = $categories ? $categories : array();
        }
        
        if($this->can_i('read-post_tag')){
            $all_tags = array();
            $params['tags'] = array();
            
            $tags = $this->PTag->getByCond([], true, false, ['name'=>'ASC']);
            if($tags){
                $all_tags = prop_as_key($tags, 'id', 'name');
                $params['tags'] = $all_tags;
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
        if(!$this->can_i('create-post_published'))
            unset($statuses[4]);
        $params['statuses'] = $statuses;
        
        if(!($new_object=$this->form->validate($object)))
            return $this->respond('post/edit', $params);
        
        if($new_object === true)
            return $this->redirect('/admin/post');
        
        // make sure user not publish it if user not allowed to publish it
        if(array_key_exists('status', $new_object)
        && $new_object['status'] == 4
        && !$this->can_i('create-post_published')){
            unset($new_object['status']);
        }
        
        // make sure user not change the slug if he's not allowed
        if($id && array_key_exists('slug', $new_object) && !$this->can_i('update-post_slug'))
            unset($new_object['slug']);
        
        // save category chain
        $to_insert_category = array();
        if(array_key_exists('category', $new_object)){
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
                        $this->PCategory->inc($cat, 'posts');
                        $this->output->delete_cache('/post/category/' . $category->slug);
                    }
                }
                
                foreach($old_categories as $cat){
                    if(!in_array($cat, $new_categories)){
                        $category = null;
                        if(array_key_exists($cat, $all_categories))
                            $category = $all_categories[$cat];
                        if(!$category)
                            continue;
                        
                        $to_delete[] = $cat;
                        $this->PCategory->dec($cat, 'posts');
                        $this->output->delete_cache('/post/category/' . $category->slug);
                    }
                }
                
                if($to_delete)
                    $this->PCChain->removeByCond(['post'=>$id, 'post_category'=>$to_delete]);

                if($to_insert)
                    $to_insert_category = $to_insert;
            }
        }
        
        // save tag chain
        $to_insert_tag = array();
        if(array_key_exists('tag', $new_object)){
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
                        $this->PTag->inc($cat, 'posts');
                        $this->output->delete_cache('/post/tag/' . $tag->slug);
                    }
                }
                
                foreach($old_tags as $cat){
                    if(!in_array($cat, $new_tags)){
                        $tag = null;
                        if(array_key_exists($cat, $all_tags))
                            $tag = $all_tags[$cat];
                        if(!$tag)
                            continue;
                        
                        $to_delete[] = $cat;
                        $this->PTag->dec($cat, 'posts');
                        $this->output->delete_cache('/post/tag/' . $tag->slug);
                    }
                }
                
                if($to_delete)
                    $this->PTChain->removeByCond(['post'=>$id, 'post_tag'=>$to_delete]);

                if($to_insert)
                    $to_insert_tag = $to_insert;
            }
        }
        
        $this->output->delete_cache('/');
        $this->cache->file->delete('_recent_posts');
        if($id)
            $this->output->delete_cache('/post/read/' . $object->slug);
        
        if($new_object){
            if(!$id){
                $new_object['user'] = $this->user->id;
                $new_object['id'] = $this->Post->create($new_object);
                $id = $new_object['id'];
            }else{
                $this->Post->set($id, $new_object);
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
            'tags' => array(),
            'statuses' => $this->enum->item('post.status'),
            'pagination' => array()
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
        
        if($this->can_i('read-post_category')){
            $this->load->model('Postcategory_model', 'PCategory');
            $all_categories = $this->PCategory->getByCond([], true);
            $params['categories'] = $all_categories;
        }
        
        if($this->can_i('read-post_tag')){
            $this->load->model('Posttag_model', 'PTag');
            $all_tags = $this->PTag->getByCond([], true);
            $params['tags'] = $all_tags;
        }
        
        $rpp = 20;
        $page= $this->input->get('page');
        if(!$page)
            $page = 1;

        $result = $this->Post->findByCond($cond, $rpp, $page);
        if($result){
            $this->load->library('ObjectFormatter', '', 'format');
            $params['posts'] = $this->format->post($result, false, false);
        }

        $total_result = $this->Post->findByCondTotal($cond);
        if($total_result > $rpp){
            $pagination_cond = $cond;
            if($filter_name)
                $pagination_cond['q'] = $filter_name;
            
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
        
        $this->Post->remove($id);
        
        $cats = $this->PCChain->getBy('post', $id);
        if($cats){
            foreach($cats as $cat){
                $this->PCategory->dec($cat->id, 'posts');
                $this->output->delete_cache('/post/category/' . $cat->slug);
            }
            
            $this->PCChain->removeBy('post', $id);
        }
        
        $tags = $this->PTChain->getBy('post', $id);
        if($tags){
            foreach($tags as $tag){
                $this->PTag->dec($tag->id, 'posts');
                $this->output->delete_cache('/post/tag/' . $tag->slug);
            }
            
            $this->PTChain->removeBy('post', $id);
        }
        
        $this->output->delete_cache('/');
        $this->output->delete_cache('/post/read/' . $post->slug);
        $this->cache->file->delete('_recent_posts'); 
        
        $this->redirect('/admin/post');
    }
}