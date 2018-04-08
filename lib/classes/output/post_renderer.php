<?php
class post_renderer extends base_renderer {
    
    public function standard_top_of_body_html() {
        global $CFG;
        
        $output = '';
        $output .= parent::standard_top_of_body_html();
        $output .= $this->opencontainers->push('div', 'main', array('class'=>'w3-row'));
        
        return $output;
    }
    
    public function sidebar($prevlink = null, $nextlink = null) {
        global $CFG;
        
        $html = $this->opencontainers->push('div', 'sidebar', array('class'=>'w3-col m4 l2 sidebar'));
        $html .= '<img src="/img/mpl_logo.png" height="183" width="183" alt="" />';
        
        $html .= $this->opencontainers->push('div', 'navlinks', array('class'=>'navlinks'));
        if ($this->page->url == $CFG->firstpost) {
            $html .= '<div class="navarrow" style="text-align:center;"><span>'.$this->icon('first_page').'</span></div>';
        } else {
            $html .= '<div class="navarrow" style="text-align:center;"><a href="'.$CFG->firstpost.'">'.$this->icon('first_page').'</a></div>';
        }
        if ($prevlink) {
            $html .= '<div class="navarrow" style="text-align:center;"><a href="'.$prevlink.'">'.$this->icon('chevron_left').'</a></div>';
        } else {
            $html .= '<div class="navarrow" style="text-align:center;"><span>'.$this->icon('chevron_left').'</span></div>';
        }
        if ($nextlink) {
            $html .= '<div class="navarrow" style="text-align:center;"><a href="'.$nextlink.'">'.$this->icon('chevron_right').'</a></div>';
        } else {
            $html .= '<div class="navarrow" style="text-align:center;"><span>'.$this->icon('chevron_right').'</span></div>';
        }
        if ($this->page->url == $CFG->lastpost) {
            $html .= '<div class="navarrow" style="text-align:center;"><span>'.$this->icon('last_page').'</span></div>';
        } else {
            $html .= '<div class="navarrow" style="text-align:center;"><a href="'.$CFG->lastpost.'">'.$this->icon('last_page').'</a></div>';
        }
        $html .= $this->opencontainers->pop('navlinks');

        $html .= $this->opencontainers->pop('sidebar');
        return $html;
    }
    
    public function post($post) {
        return '<div class="w3-col m8 l9 w3-container post">'.$post."</div>\n".'<div class="w3-col l1 w3-hide-medium"></div>';
    }

}