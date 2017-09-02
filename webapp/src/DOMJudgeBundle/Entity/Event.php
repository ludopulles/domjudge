<?php

namespace DOMJudgeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Log of all events during a contest
 *
 * @ORM\Table(name="event", options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}, uniqueConstraints={@ORM\UniqueConstraint(name="eventtime", columns={"eventtime"})}, indexes={@ORM\Index(name="cid", columns={"cid"}), @ORM\Index(name="datatype", columns={"datatype"})})
 * @ORM\Entity
 */
class Event
{
	/**
	 * @var integer
	 *
	 * @ORM\Id
	 * @ORM\Column(name="eventid", type="integer", nullable=false, options={"comment"="Unique ID"})
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private $eventid;

	/**
	 * @var double
	 *
	 * @ORM\Column(name="eventtime", type="decimal", precision=32, scale=9, nullable=false)
	 */
	private $eventtime;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="datatype", type="string", length=25, nullable=false)
	 */
	private $datatype;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="dataid", type="string", length=50, nullable=false)
	 */
	private $dataid;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="action", type="string", length=30, nullable=false)
	 */
	private $action;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="content", type="blob", nullable=true)
	 */
	private $content;

	/**
	 * @var \Contest
	 *
	 * @ORM\ManyToOne(targetEntity="Contest")
	 * @ORM\JoinColumns({
	 *   @ORM\JoinColumn(name="cid", referencedColumnName="cid")
	 * })
	 */
	private $cid;

	/**
	 * Set eventid
	 *
	 * @param integer $eventid
	 *
	 * @return Event
	 */
	public function setEventid($eventid)
	{
		$this->eventid = $eventid;

		return $this;
	}

	/**
	 * Get eventid
	 *
	 * @return integer
	 */
	public function getEventid()
	{
		return $this->eventid;
	}

	/**
	 * Set eventtime
	 *
	 * @param double $eventtime
	 *
	 * @return Event
	 */
	public function setEventtime($eventtime)
	{
		$this->eventtime = $eventtime;

		return $this;
	}

	/**
	 * Get eventtime
	 *
	 * @return double
	 */
	public function getEventtime()
	{
		return $this->eventtime;
	}

	/**
	 * Set cid
	 *
	 * @param integer $cid
	 *
	 * @return Event
	 */
	public function setCid($cid)
	{
		$this->cid = $cid;

		return $this;
	}

	/**
	 * Get cid
	 *
	 * @return integer
	 */
	public function getCid()
	{
		return $this->cid;
	}

	/**
	 * Set datatype
	 *
	 * @param string $datatype
	 *
	 * @return Event
	 */
	public function setDatatype($datatype)
	{
		$this->datatype = $datatype;

		return $this;
	}

	/**
	 * Get datatype
	 *
	 * @return string
	 */
	public function getDatatype()
	{
		return $this->datatype;
	}

	/**
	 * Set dataid
	 *
	 * @param string $dataid
	 *
	 * @return Event
	 */
	public function setDataid($dataid)
	{
		$this->dataid = $dataid;

		return $this;
	}

	/**
	 * Get dataid
	 *
	 * @return string
	 */
	public function getDataid()
	{
		return $this->dataid;
	}

	/**
	 * Set action
	 *
	 * @param string $action
	 *
	 * @return Event
	 */
	public function setAction($action)
	{
		$this->action = $action;

		return $this;
	}

	/**
	 * Get action
	 *
	 * @return string
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * Set content
	 *
	 * @param string $content
	 *
	 * @return Event
	 */
	public function setContent($content)
	{
		$this->content = $content;

		return $this;
	}

	/**
	 * Get content
	 *
	 * @return string
	 */
	public function getContent()
	{
		return $this->content;
	}
}
