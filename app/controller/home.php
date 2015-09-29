<?php


class Home extends Controller
{
    
    public function index()
    {

        
       
    }
    public function get()
    {
         $orders  = $this->oAPI->getOrders(array('limit' => 10));
        
        var_dump($orders);
       
    }
    
}
