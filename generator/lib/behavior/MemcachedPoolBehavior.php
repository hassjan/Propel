<?php
require_once __DIR__.'/../util/PropelPHPParser.php';
class MemcachedPoolBehavior extends Behavior
{
    
    /**
     * Filter to add the CacheHandler class to the Peer objects so they
     * can use Memcached or whatever other cache you want to use
     */
    public function peerFilter(&$script)
    {
        $keyname = $this->getTable()->getPhpName();
        $newAddInstanceToPool = "
    public static function addInstanceToPool(\$obj, \$key = null)
    {
        if (Propel::isInstancePoolingEnabled()) {
            if (\$key === null) {
                \$key = (string) \$obj->getId();
            } // if key === null
            \$cache = \\AdApp\\Poolcache\\CacheHandler::getInstance();
            \$pool = \$cache->getPool('%s');
            \$pool[\$key] = \$obj;
            \$cache->setPool('%s', \$pool);
            /*self::\$instances[\$key] = \$obj;*/
        }
    }
    ";
        $newAddInstanceToPool = sprintf($newAddInstanceToPool, $keyname, $keyname);
        $parser = new PropelPHPParser($script, true);
        $parser->replaceMethod('addInstanceToPool', $newAddInstanceToPool);
        $script = $parser->getCode();
        
        $newRemoveInstanceFromPool = "
    public static function removeInstanceFromPool(\$value)
    {
        if (Propel::isInstancePoolingEnabled() && \$value !== null) {
            if (is_object(\$value) && \$value instanceof %s) {
                \$key = (string) \$value->getId();
            } elseif (is_scalar(\$value)) {
                // assume we've been passed a primary key
                \$key = (string) \$value;
            } else {
                \$e = new PropelException(\"Invalid value passed to removeInstanceFromPool().
                    Expected primary key or %s object; got \" .
                    (is_object(\$value) ? get_class(\$value) . ' object.' : var_export(\$value,true)));
                throw \$e;
            }
            \$cache = \\AdApp\\Poolcache\\CacheHandler::getInstance();
            \$pool = \$cache->getPool('%s');
            unset(\$pool[\$key]);
            \$cache->setPool('%s', \$pool);
            /*unset(self::\$instances[\$key]);*/
        }
    }
    ";
          
        $newRemoveInstanceFromPool = sprintf($newRemoveInstanceFromPool, $keyname, $keyname, $keyname, $keyname);
        //$parser = new PropelPHPParser($script, true);
        $parser->replaceMethod('removeInstanceFromPool', $newRemoveInstanceFromPool);
        $script = $parser->getCode();
        
        $newGetInstanceFromPool = "
    public static function getInstanceFromPool(\$key)
    {
        if (Propel::isInstancePoolingEnabled()) {
            \$cache = \\AdApp\\Poolcache\\CacheHandler::getInstance();
            \$pool = \$cache->getPool('%s');
            if (isset(\$pool[\$key])) {
                return \$pool[\$key];
            }
        }
        return null; // just to be explicit
    }
    ";
        
        $newGetInstanceFromPool = sprintf($newGetInstanceFromPool, $keyname);
        //$parser = new PropelPHPParser($script, true);
        $parser->replaceMethod('getInstanceFromPool', $newGetInstanceFromPool);
        $script = $parser->getCode();
        
        $newClearInstancePool = "
    public static function clearInstancePool()
    {
        \$cache = \\AdApp\\Poolcache\\CacheHandler::getInstance();
        \$cache->clearPool('%s');
        /*self::\$instances = array();*/
    }
    ";
           
        $newClearInstancePool = sprintf($newClearInstancePool, $keyname);
        //$parser = new PropelPHPParser($script, true);
        $parser->replaceMethod('clearInstancePool', $newClearInstancePool);
        $script = $parser->getCode();
    }
    
}
