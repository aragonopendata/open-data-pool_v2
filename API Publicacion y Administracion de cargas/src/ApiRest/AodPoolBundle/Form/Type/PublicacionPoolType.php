<?php // File: AodPool/src/ApiRest/AodPoolBundle/Form/Type/AodPoolType.php
      

namespace ApiRest\AodPoolBundle\Form\Type;

use Symfony\Component\Form\AbstractType; 
use Symfony\Component\Form\FormBuilderInterface; 
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use nelmio\apidocbundle\util\LegacyFormHelper;
use Symfony\Component\HttpFoundation\File\File;


class PublicacionPoolType extends AbstractType 
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
	public function buildForm(FormBuilderInterface $builder, array $options) 
	{ 
        $builder
            ->add('idesquema',TextType::class,
            [
                'required' => true,
                'description' => "Identificador del esquema Ejemplo: Municipios (sin .xml)",

            ])
           ->add('csv',TextType::class,
            [
                'required' => true,
                'description' => "Archivo CSV con los datos. Puede ser de File (subiendo el archivo) o de tipo TextArea (pegando el texto). 
                                  El CSV ha de estar separado por  ';'. No copiar desde Excel ya que siempre inserta tabulador como elemento separador.",
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
                'data_class' => 'ApiRest\AodPoolBundle\Entity\Publicacion',
            ]
	    );
	}
}


