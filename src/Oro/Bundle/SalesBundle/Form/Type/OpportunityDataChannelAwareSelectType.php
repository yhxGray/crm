<?php
namespace Oro\Bundle\SalesBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class OpportunityDataChannelAwareSelectType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_sales_opportunity_data_channel_aware_select';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_entity_create_or_select_inline_channel_aware';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias'           => 'opportunities',
                'create_form_route'            => 'oro_sales_opportunity_data_channel_aware_create',
                'grid_view_widget_route'       => 'oro_sales_datagrid_opportunity_datachannel_aware',
                'configs'                      => [
                    'placeholder' => 'oro.sales.form.choose_opportunity'
                ],
                'channel_field'                => 'dataChannel',
                'channel_required'             => true,
                'existing_entity_grid_id'      => 'id',
                'create_enabled'               => true,
                'create_acl'                   => null,
                'create_form_route_parameters' => [],
                'grid_widget_route'            => 'oro_datagrid_widget',
                'grid_name'                    => null,
                'grid_parameters'              => [],
                'grid_render_parameters'       => []
            ]
        );
    }
}
