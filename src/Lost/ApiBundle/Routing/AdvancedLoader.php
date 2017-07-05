<?php
namespace Dispensaries\ApiBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

class AdvancedLoader extends Loader
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function load($resource, $type = null)
    {
        //echo "<pre>"; print_r(); exit;
        
        $collection = new RouteCollection();

        $version = $this->request->get('version');
        
        //echo $verion; exit;
        
        if(!empty($version) && $version != '1.0'){
            $resource = '@DispensariesApiBundle/Resources/config/routing_'.$version.'.yml';
        }
        //echo $resource; exit;
        $type = 'yaml';

        $importedRoutes = $this->import($resource, $type);

        $collection->addCollection($importedRoutes);
        $this->loaded = true;
        return $collection;
    }

    public function supports($resource, $type = null)
    {
        return 'advanced_extra' === $type;
    }
}