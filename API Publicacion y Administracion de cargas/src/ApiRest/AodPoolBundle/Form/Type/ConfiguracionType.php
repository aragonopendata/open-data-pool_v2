<?php // File: AodPool/src/ApiRest/AodPoolBundle/Form/Type/AodPoolType.php
      

namespace ApiRest\AodPoolBundle\Form\Type;

use Symfony\Component\Form\AbstractType; 
use Symfony\Component\Form\FormBuilderInterface; 
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use nelmio\apidocbundle\util\LegacyFormHelper;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Formulario de alta de Configuracion
 */
class ConfiguracionType extends AbstractType 
{ 
    public function getBlockPrefix()
    {
        return "";
    }

    /**
     * Get name
     * @return string
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

	/** 
     * @param FormBuilderInterface  $builder 
     * @param array                 $options 
    */ 
	public function buildForm(FormBuilderInterface $builder,array $options) 
	{ 
        $builder
        ->add('nombre',TextType::class,
            [
                'required' => true,
                'description' => "Nombre de la configuración rdf. La configuración se guardará con este nombre y se invocará con el mismo",
            ])
        ->add('tipo',TextType::class,
            [
                'required' => true,
                'description' => "Tipo de la configuración rdf. Puede ser el rdf:type o el dc:type del esquema semántico",
            ])
        ->add('yml',TextType::class,
            [
                'required' => true,
                'description' => "Archivo yml con los datos de la configuración. Puede ser de File (subiendo el archivo) o de tipo TextArea (pegando el texto).",
            ]
        );
    }

	/**
	 * @param OptionsResolver $resolver
	 */
	public function configureOptions(OptionsResolver $resolver)
	{

	    $resolver->setDefaults(
            [
                'csrf_protection' => false,
                'mapped' => false,
                'allow_extra_fields' => true,
                'data_class' => 'ApiRest\AodPoolBundle\Entity\Configuracion',
            ]
	    );
	}
}


