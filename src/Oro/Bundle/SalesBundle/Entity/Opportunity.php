<?php

namespace Oro\Bundle\SalesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrencyHolderInterface;
use Oro\Bundle\DataAuditBundle\Metadata\Annotation as Oro;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\ChannelBundle\Model\ChannelEntityTrait;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\SalesBundle\Model\ExtendOpportunity;
use Oro\Bundle\ChannelBundle\Model\ChannelAwareInterface;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\SalesBundle\Entity\Repository\OpportunityRepository")
 * @ORM\Table(
 *      name="orocrm_sales_opportunity",
 *      indexes={@ORM\Index(name="opportunity_created_idx",columns={"created_at"})}
 * )
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      routeName="oro_sales_opportunity_index",
 *      routeView="oro_sales_opportunity_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-usd"
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *              "category"="sales_data",
 *              "field_acl_supported" = "true"
 *          },
 *          "form"={
 *              "form_type"="oro_sales_opportunity_select",
 *              "grid_name"="sales-opportunity-grid",
 *          },
 *          "dataaudit"={
 *              "auditable"=true,
 *              "immutable"=true
 *          },
 *          "grid"={
 *              "default"="sales-opportunity-grid",
 *              "context"="sales-opportunity-for-context-grid"
 *          },
 *          "tag"={
 *              "enabled"=true
 *          }
 *     }
 * )
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Opportunity extends ExtendOpportunity implements
    EmailHolderInterface,
    ChannelAwareInterface,
    MultiCurrencyHolderInterface
{
    use ChannelEntityTrait;

    const INTERNAL_STATUS_CODE = 'opportunity_status';

    const STATUS_LOST = 'lost';
    const STATUS_WON  = 'won';

    /**
     * The key in system config for probability - status map
     */
    const PROBABILITIES_CONFIG_KEY = 'oro_sales.default_opportunity_probabilities';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *  defaultValues={
     *      "importexport"={
     *          "order"=0
     *      }
     *  }
     * )
     */
    protected $id;

    /**
     * @var OpportunityCloseReason
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\SalesBundle\Entity\OpportunityCloseReason")
     * @ORM\JoinColumn(name="close_reason_name", referencedColumnName="name")
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true},
     *      "importexport"={
     *          "order"=100,
     *          "short"=true
     *      }
     *  }
     * )
     **/
    protected $closeReason;

    /**
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ContactBundle\Entity\Contact", cascade={"persist"})
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true},
     *      "importexport"={
     *          "order"=120,
     *          "short"=true
     *      },
     *      "form"={
     *          "form_type"="oro_contact_select"
     *      }
     *  }
     * )
     **/
    protected $contact;

    /**
     * @var Lead
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\SalesBundle\Entity\Lead", inversedBy="opportunities")
     * @ORM\JoinColumn(name="lead_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true},
     *      "importexport"={
     *          "order"=130,
     *          "short"=true
     *      }
     *  }
     * )
     **/
    protected $lead;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true, "immutable"=true},
     *      "importexport"={
     *          "order"=140,
     *          "short"=true
     *      }
     *  }
     * )
     */
    protected $owner;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true},
     *      "importexport"={
     *          "order"=10,
     *          "identity"=true
     *      }
     *  }
     * )
     */
    protected $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="close_date", type="date", nullable=true)
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true, "immutable"=true},
     *      "importexport"={
     *          "order"=20
     *      }
     *  }
     * )
     */
    protected $closeDate;

    /**
     * @var float
     *
     * @ORM\Column(name="probability", type="percent", nullable=true)
     * @ConfigField(
     *  defaultValues={
     *      "form"={
     *          "form_type"="oro_percent",
     *          "form_options"={
     *              "constraints"={{"Range":{"min":0, "max":100}}},
     *          }
     *      },
     *      "dataaudit"={"auditable"=true, "immutable"=true},
     *      "importexport"={
     *          "order"=30
     *      }
     *  }
     * )
     */
    protected $probability;

    /**
     * Changes to this value object wont affect entity change set
     * To change persisted price value you should create and set new Multicurrency
     *
     * @var Multicurrency
     */
    protected $budgetAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="budget_amount_currency", type="currency", length=3, nullable=true)
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true, "immutable"=true},
     *      "importexport"={
     *          "order"=55
     *      }
     *  }
     * )
     */
    protected $budgetAmountCurrency;

    /**
     * @var double
     *
     * @ORM\Column(name="budget_amount_value", type="money_value", nullable=true)
     * @ConfigField(
     *  defaultValues={
     *      "form"={
     *          "form_type"="oro_money",
     *          "form_options"={
     *              "constraints"={{"Range":{"min":0}}},
     *          }
     *      },
     *      "dataaudit"={
     *          "auditable"=true
     *      },
     *      "importexport"={
     *          "order"=50
     *      },
     *     "multicurrency"={
     *          "target" = "budgetAmount",
     *          "virtual_field" = "budgetAmountBaseCurrency"
     *      }
     *  }
     * )
     */
    protected $budgetAmountValue;

    /**
     * Changes to this value object wont affect entity change set
     * To change persisted price value you should create and set new Multicurrency
     *
     * @var Multicurrency
     */
    protected $closeRevenue;

    /**
     * @var string
     *
     * @ORM\Column(name="close_revenue_currency", type="currency", length=3, nullable=true)
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true, "immutable"=true},
     *      "importexport"={
     *          "order"=65
     *      }
     *  }
     * )
     */
    protected $closeRevenueCurrency;

    /**
     * @var double
     *
     * @ORM\Column(name="close_revenue_value", type="money_value", nullable=true)
     * @ConfigField(
     *  defaultValues={
     *      "form"={
     *          "form_type"="oro_money",
     *          "form_options"={
     *              "constraints"={{"Range":{"min":0}}},
     *          }
     *      },
     *      "dataaudit"={
     *          "auditable"=true
     *      },
     *      "importexport"={
     *          "order"=60
     *      },
     *      "multicurrency"={
     *          "target" = "closeRevenue",
     *          "virtual_field" = "closeRevenueBaseCurrency"
     *      }
     *  }
     * )
     */
    protected $closeRevenueValue;

    /**
     * @var string
     *
     * @ORM\Column(name="customer_need", type="text", nullable=true)
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true},
     *      "importexport"={
     *          "order"=60
     *      }
     *  }
     * )
     */
    protected $customerNeed;

    /**
     * @var string
     *
     * @ORM\Column(name="proposed_solution", type="text", nullable=true)
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true},
     *      "importexport"={
     *          "order"=70
     *      }
     *  }
     * )
     */
    protected $proposedSolution;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $updatedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=80
     *          }
     *      }
     * )
     */
    protected $notes;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * @var B2bCustomer
     *
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\SalesBundle\Entity\B2bCustomer",
     *     inversedBy="opportunities",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true},
     *      "importexport"={
     *          "order"=110,
     *          "short"=true
     *      },
     *      "form"={
     *          "form_type"="oro_sales_b2bcustomer_select"
     *      }
     *  }
     * )
     */
    protected $customer;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", name="closed_at", nullable=true)
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true, "immutable"=true},
     *      "importexport"={"excluded"=true, "immutable"=true}
     *  }
     * )
     */
    protected $closedAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  Lead        $lead
     * @return Opportunity
     */
    public function setLead($lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return MultiCurrency|null
     */
    public function getBudgetAmount()
    {
        return $this->budgetAmount;
    }

    /**
     * @param MultiCurrency $budgetAmount|null
     * @return Opportunity
     */
    public function setBudgetAmount(MultiCurrency $budgetAmount = null)
    {
        $this->budgetAmount = $budgetAmount;
        $this->updateMultiCurrencyFields();

        return $this;
    }

    /**
     * @ORM\PostLoad
     */
    public function loadMultiCurrencyFields()
    {
        $this->budgetAmount = MultiCurrency::create($this->budgetAmountValue, $this->budgetAmountCurrency);
        $this->closeRevenue = MultiCurrency::create($this->closeRevenueValue, $this->closeRevenueCurrency);
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     *
     * @return void
     */
    public function updateMultiCurrencyFields()
    {
        if ($this->budgetAmount) {
            $this->budgetAmountValue = $this->budgetAmount->getValue();
            $currency = $this->budgetAmount->getValue() !== null ?
                $this->budgetAmount->getCurrency() :
                null;
            $this->budgetAmountCurrency = $currency;
        }
        if ($this->closeRevenue) {
            $this->closeRevenueValue = $this->closeRevenue->getValue();
            $currency = $this->closeRevenue->getValue() !== null ?
                $this->closeRevenue->getCurrency() :
                null;
            $this->closeRevenueCurrency = $currency;
        }
    }

    /**
     * @param string $currency
     * @return Opportunity
     */
    public function setBudgetAmountCurrency($currency)
    {
        $this->budgetAmountCurrency = $currency;

        if ($this->budgetAmount instanceof MultiCurrency) {
            $this->budgetAmount->setCurrency($currency);
        }

        return $this;
    }

    /**
     * @param string $budgetAmountValue
     * @return Opportunity
     */
    public function setBudgetAmountValue($budgetAmountValue)
    {
        $this->budgetAmountValue = $budgetAmountValue;

        if ($this->budgetAmount instanceof MultiCurrency) {
            $this->budgetAmount->setValue($budgetAmountValue);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getBudgetAmountCurrency()
    {
        return $this->budgetAmountCurrency;
    }

    /**
     * @return float
     */
    public function getBudgetAmountValue()
    {
        return $this->budgetAmountValue;
    }

    /**
     * @param string $currency
     * @return Opportunity
     */
    public function setCloseRevenueCurrency($currency)
    {
        $this->closeRevenueCurrency = $currency;

        if ($this->closeRevenue instanceof MultiCurrency) {
            $this->closeRevenue->setCurrency($currency);
        }

        return $this;
    }

    /**
     * @param float $closeRevenueValue
     * @return Opportunity
     */
    public function setCloseRevenueValue($closeRevenueValue)
    {
        $this->closeRevenueValue = $closeRevenueValue;

        if ($this->closeRevenue instanceof MultiCurrency) {
            $this->closeRevenue->setValue($closeRevenueValue);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getCloseRevenueCurrency()
    {
        return $this->closeRevenueCurrency;
    }

    /**
     * @return float
     */
    public function getCloseRevenueValue()
    {
        return $this->closeRevenueValue;
    }

    /**
     * @param  \DateTime   $closeDate
     * @return Opportunity
     */
    public function setCloseDate($closeDate)
    {
        $this->closeDate = $closeDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCloseDate()
    {
        return $this->closeDate;
    }

    /**
     * @param  Contact     $contact
     * @return Opportunity
     */
    public function setContact($contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return Contact
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @param  string      $customerNeed
     * @return Opportunity
     */
    public function setCustomerNeed($customerNeed)
    {
        $this->customerNeed = $customerNeed;

        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerNeed()
    {
        return $this->customerNeed;
    }

    /**
     * @param  float       $probability
     * @return Opportunity
     */
    public function setProbability($probability)
    {
        $this->probability = $probability;

        return $this;
    }

    /**
     * @return float
     */
    public function getProbability()
    {
        return $this->probability;
    }

    /**
     * @param  string      $proposedSolution
     * @return Opportunity
     */
    public function setProposedSolution($proposedSolution)
    {
        $this->proposedSolution = $proposedSolution;

        return $this;
    }

    /**
     * @return string
     */
    public function getProposedSolution()
    {
        return $this->proposedSolution;
    }

    /**
     * @param  string      $name
     * @return Opportunity
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param  OpportunityCloseReason $closeReason
     * @return Opportunity
     */
    public function setCloseReason($closeReason)
    {
        $this->closeReason = $closeReason;

        return $this;
    }

    /**
     * @return OpportunityCloseReason
     */
    public function getCloseReason()
    {
        return $this->closeReason;
    }

    /**
     * @param MultiCurrency $closeRevenue|null
     * @return Opportunity
     */
    public function setCloseRevenue(MultiCurrency $closeRevenue = null)
    {
        if ($closeRevenue) {
            $this->closeRevenue = $closeRevenue;
            $this->updateMultiCurrencyFields();
        }

        return $this;
    }

    /**
     * @return MultiCurrency|null
     */
    public function getCloseRevenue()
    {
        return $this->closeRevenue;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param  \DateTime   $created
     * @return Opportunity
     */
    public function setCreatedAt($created)
    {
        $this->createdAt = $created;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param  \DateTime   $updated
     * @return Opportunity
     */
    public function setUpdatedAt($updated)
    {
        $this->updatedAt = $updated;

        return $this;
    }

    /**
     * Get the primary email address of the related contact
     *
     * @return string
     */
    public function getEmail()
    {
        $contact = $this->getContact();
        if (!$contact) {
            return null;
        }

        return $contact->getEmail();
    }

    public function __toString()
    {
        return (string) $this->getName();
    }
    /**
     * @ORM\PrePersist
     */
    public function beforeSave()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->beforeUpdate();
    }

    /**
     * @ORM\PreUpdate
     */
    public function beforeUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param  User        $owningUser
     * @return Opportunity
     */
    public function setOwner($owningUser)
    {
        $this->owner = $owningUser;

        return $this;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param  string      $notes
     * @return Opportunity
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @param B2bCustomer $customer
     * @TODO remove null after BAP-5248
     */
    public function setCustomer(B2bCustomer $customer = null)
    {
        $this->customer = $customer;
    }

    /**
     * @return B2bCustomer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Set organization
     *
     * @param Organization $organization
     * @return Opportunity
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Remove Customer
     *
     * @return Lead
     */
    public function removeCustomer()
    {
        $this->customer = null;
    }

    /**
     * @param \DateTime $closedAt
     */
    public function setClosedAt(\DateTime $closedAt = null)
    {
        $this->closedAt = $closedAt;
    }

    /**
     * @return \DateTime
     */
    public function getClosedAt()
    {
        return $this->closedAt;
    }
}
