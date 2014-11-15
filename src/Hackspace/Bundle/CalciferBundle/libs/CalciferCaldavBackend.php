<?php
/**
 * Created by PhpStorm.
 * User: tim
 * Date: 15.11.14
 * Time: 17:13
 */

namespace Hackspace\Bundle\CalciferBundle\libs;


use Jsvrcek\ICS\CalendarStream;
use Jsvrcek\ICS\Model\CalendarEvent;
use Jsvrcek\ICS\Utility\Formatter;
use Sabre\CalDAV\Backend\AbstractBackend;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Hackspace\Bundle\CalciferBundle\Entity\Event;

class CalciferCaldavBackend extends AbstractBackend
{
    /** @var Controller */
    private $controller = null;

    function __construct(Controller $controller)
    {
        $this->controller = $controller;
    }

    /**
     * Returns a list of calendars for a principal.
     *
     * Every project is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the
     *    calendar. This can be the same as the uri or a database key.
     *  * uri, which the basename of the uri with which the calendar is
     *    accessed.
     *  * principaluri. The owner of the calendar. Almost always the same as
     *    principalUri passed to this method.
     *
     * Furthermore it can contain webdav properties in clark notation. A very
     * common one is '{DAV:}displayname'.
     *
     * Many clients also require:
     * {urn:ietf:params:xml:ns:caldav}supported-calendar-component-set
     * For this property, you can just return an instance of
     * Sabre\CalDAV\Property\SupportedCalendarComponentSet.
     *
     * @param string $principalUri
     * @return array
     */
    public function getCalendarsForUser($principalUri)
    {
        return [[
            'id' => 1,
            'uri' => 'calendar',
            'principaluri' => '/caldav/calcifer',
        ]];
    }

    /**
     * Creates a new calendar for a principal.
     *
     * If the creation was a success, an id must be returned that can be used to reference
     * this calendar in other methods, such as updateCalendar.
     *
     * @param string $principalUri
     * @param string $calendarUri
     * @param array $properties
     * @return void
     */
    public function createCalendar($principalUri, $calendarUri, array $properties)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Delete a calendar and all it's objects
     *
     * @param mixed $calendarId
     * @return void
     */
    public function deleteCalendar($calendarId)
    {
        throw new \Exception('Not implemented');
    }

    private function FormatCalendarEvent(CalendarEvent $event)
    {
        $stream = new CalendarStream();
        $formatter = new Formatter();
        $stream->addItem('BEGIN:VEVENT')
            ->addItem('UID:' . $event->getUid())
            ->addItem('DTSTART:' . $formatter->getFormattedUTCDateTime($event->getStart()))
            ->addItem('DTEND:' . $formatter->getFormattedUTCDateTime($event->getEnd()))
            ->addItem('SUMMARY:' . $event->getSummary())
            ->addItem('DESCRIPTION:' . $event->getDescription());

        if ($event->getClass())
            $stream->addItem('CLASS:' . $event->getClass());

        /* @var $location Location */
        foreach ($event->getLocations() as $location) {
            $stream
                ->addItem('LOCATION' . $location->getUri() . $location->getLanguage() . ':' . $location->getName());
        }

        if ($event->getGeo())
            $stream->addItem('GEO:' . $event->getGeo()->getLatitude() . ';' . $event->getGeo()->getLongitude());

        if ($event->getUrl())
            $stream->addItem('URL:' . $event->getUrl());

        if ($event->getCreated())
            $stream->addItem('CREATED:' . $formatter->getFormattedUTCDateTime($event->getCreated()));

        if ($event->getLastModified())
            $stream->addItem('LAST-MODIFIED:' . $formatter->getFormattedUTCDateTime($event->getLastModified()));

        foreach ($event->getAttendees() as $attendee) {
            $stream->addItem($attendee->__toString());
        }

        if ($event->getOrganizer())
            $stream->addItem($event->getOrganizer()->__toString());

        $stream->addItem('END:VEVENT');

        return $stream->getStream();
    }

    private function formatEvent(Event $event)
    {
        /** @var CalendarEvent $calendar_event */
        $calendar_event = $event->ConvertToCalendarEvent();
        $calendar_data = $this->FormatCalendarEvent($calendar_event);

        $event_data = [
            'id' => $event->id,
            'uri' => $event->slug,
            'lastmodified' => $event->startdate,
            'etag' => '"' . sha1($calendar_data) . '"',
            'calendarid' => 1,
            'calendardata' => $calendar_data,
            'size' => strlen($calendar_data),
            'component' => 'VEVENT',
        ];
        return $event_data;
    }

    /**
     * Returns all calendar objects within a calendar.
     *
     * Every item contains an array with the following keys:
     *   * id - unique identifier which will be used for subsequent updates
     *   * calendardata - The iCalendar-compatible calendar data
     *   * uri - a unique key which will be used to construct the uri. This can
     *     be any arbitrary string, but making sure it ends with '.ics' is a
     *     good idea. This is only the basename, or filename, not the full
     *     path.
     *   * lastmodified - a timestamp of the last modification time
     *   * etag - An arbitrary string, surrounded by double-quotes. (e.g.:
     *   '"abcdef"')
     *   * calendarid - The calendarid as it was passed to this function.
     *   * size - The size of the calendar objects, in bytes.
     *   * component - optional, a string containing the type of object, such
     *     as 'vevent' or 'vtodo'. If specified, this will be used to populate
     *     the Content-Type header.
     *
     * Note that the etag is optional, but it's highly encouraged to return for
     * speed reasons.
     *
     * The calendardata is also optional. If it's not returned
     * 'getCalendarObject' will be called later, which *is* expected to return
     * calendardata.
     *
     * If neither etag or size are specified, the calendardata will be
     * used/fetched to determine these numbers. If both are specified the
     * amount of times this is needed is reduced by a great degree.
     *
     * @param mixed $calendarId
     * @return array
     */
    public function getCalendarObjects($calendarId)
    {
        /** @var EntityManager $em */
        $em = $this->controller->getDoctrine()->getManager();

        $now = new \DateTime();
        $now->setTime(0, 0, 0);
        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();
        $qb->select(array('e'))
            ->from('CalciferBundle:Event', 'e')
            ->where('e.startdate >= :startdate')
            ->orderBy('e.startdate')
            ->setParameter('startdate', $now);
        $entities = $qb->getQuery()->execute();

        if (count($entities) > 0) {
            $events = [];
            foreach ($entities as $event) {
                /** @var Event $event */
                $events[] = $this->formatEvent($event);
            }
            return $events;
        }
    }

    /**
     * Returns information from a single calendar object, based on it's object
     * uri.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * The returned array must have the same keys as getCalendarObjects. The
     * 'calendardata' object is required here though, while it's not required
     * for getCalendarObjects.
     *
     * This method must return null if the object did not exist.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @return array|null
     */
    public function getCalendarObject($calendarId, $objectUri)
    {
        /** @var EntityManager $em */
        $em = $this->controller->getDoctrine()->getManager();

        /** @var EntityRepository $repo */
        $repo = $em->getRepository('CalciferBundle:Event');

        /** @var Event $entity */
        $event = $repo->findOneBy(['slug' => $objectUri]);

        if (!($event instanceof Event)) {
            throw $this->controller->createNotFoundException('Unable to find Event entity.');
        }

        return $this->formatEvent($event);
    }

    /**
     * Creates a new calendar object.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * It is possible return an etag from this function, which will be used in
     * the response to this PUT request. Note that the ETag must be surrounded
     * by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return string|null
     */
    public function createCalendarObject($calendarId, $objectUri, $calendarData)
    {
        // TODO: Implement createCalendarObject() method.
    }

    /**
     * Updates an existing calendarobject, based on it's uri.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * It is possible return an etag from this function, which will be used in
     * the response to this PUT request. Note that the ETag must be surrounded
     * by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return string|null
     */
    public function updateCalendarObject($calendarId, $objectUri, $calendarData)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Deletes an existing calendar object.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @return void
     */
    public function deleteCalendarObject($calendarId, $objectUri)
    {
        throw new \Exception('Not implemented');
    }
}