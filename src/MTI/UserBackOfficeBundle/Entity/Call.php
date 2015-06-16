<?php

namespace MTI\UserBackOfficeBundle\Entity;

class Call
{
	protected $id;

	protected $userid;

	protected $date;

	protected $created;
    /**
     * @var integer
     */
    private $type;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set userid
     *
     * @param integer $userid
     * @return Call
     */
    public function setUserid($userid)
    {
        $this->userid = $userid;

        return $this;
    }

    /**
     * Get userid
     *
     * @return integer 
     */
    public function getUserid()
    {
        return $this->userid;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return Call
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer 
     */
    public function getType()
    {
        return $this->type;
    }

    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedValue()
    {
         $this->created = new \DateTime();
    }
}
