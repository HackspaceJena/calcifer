<?php
/**
 * Created by PhpStorm.
 * User: tim
 * Date: 15.11.14
 * Time: 19:45
 */

namespace Hackspace\Bundle\CalciferBundle\libs;

use
    Sabre\DAV,
    Sabre\DAVACL,
    Sabre\HTTP\URLUtil;

class CalciferPrincipalBackend extends DAVACL\PrincipalBackend\AbstractBackend
{

    /**
     * Returns a list of principals based on a prefix.
     *
     * This prefix will often contain something like 'principals'. You are only
     * expected to return principals that are in this base path.
     *
     * You are expected to return at least a 'uri' for every user, you can
     * return any additional properties if you wish so. Common properties are:
     *   {DAV:}displayname
     *   {http://sabredav.org/ns}email-address - This is a custom SabreDAV
     *     field that's actually injected in a number of other properties. If
     *     you have an email address, use this property.
     *
     * @param string $prefixPath
     * @return array
     */
    function getPrincipalsByPrefix($prefixPath)
    {
        return [
            [
                '{DAV:}displayname' => 'calcifer',
                '{http://sabredav.org/ns}email-address' => 'calcifer@example.com',
                'uri' => '/caldav/calcifer',
            ]
        ];
    }

    /**
     * Returns a specific principal, specified by it's path.
     * The returned structure should be the exact same as from
     * getPrincipalsByPrefix.
     *
     * @param string $path
     * @return array
     */
    function getPrincipalByPath($path)
    {
        return [
            '{DAV:}displayname' => 'calcifer',
            '{http://sabredav.org/ns}email-address' => 'calcifer@example.com',
            'uri' => '/caldav/calcifer',
        ];
    }

    /**
     * Updates one ore more webdav properties on a principal.
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documenation for more info and examples.
     *
     * @param string $path
     * @param \Sabre\DAV\PropPatch $propPatch
     * @return void
     */
    function updatePrincipal($path, \Sabre\DAV\PropPatch $propPatch)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * This method is used to search for principals matching a set of
     * properties.
     *
     * This search is specifically used by RFC3744's principal-property-search
     * REPORT. You should at least allow searching on
     * http://sabredav.org/ns}email-address.
     *
     * The actual search should be a unicode-non-case-sensitive search. The
     * keys in searchProperties are the WebDAV property names, while the values
     * are the property values to search on.
     *
     * If multiple properties are being searched on, the search should be
     * AND'ed.
     *
     * This method should simply return an array with full principal uri's.
     *
     * If somebody attempted to search on a property the backend does not
     * support, you should simply return 0 results.
     *
     * You can also just return 0 results if you choose to not support
     * searching at all, but keep in mind that this may stop certain features
     * from working.
     *
     * @param string $prefixPath
     * @param array $searchProperties
     * @return array
     */
    function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof')
    {
        return [
            [
                '{DAV:}displayname' => 'calcifer',
                '{http://sabredav.org/ns}email-address' => 'calcifer@example.com',
                'uri' => '/caldav/calcifer',
            ]
        ];
    }

    /**
     * Returns the list of members for a group-principal
     *
     * @param string $principal
     * @return array
     */
    function getGroupMemberSet($principal)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Returns the list of groups a principal is a member of
     *
     * @param string $principal
     * @return array
     */
    function getGroupMembership($principal)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Updates the list of group members for a group principal.
     *
     * The principals should be passed as a list of uri's.
     *
     * @param string $principal
     * @param array $members
     * @return void
     */
    function setGroupMemberSet($principal, array $members)
    {
        throw new \Exception('Not implemented');
    }
}