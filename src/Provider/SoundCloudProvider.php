<?php

namespace MediaMonks\SonataMediaBundle\Provider;

use MediaMonks\SonataMediaBundle\Exception\InvalidProviderUrlException;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\Form\Type\ImmutableArrayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SoundCloudProvider extends AbstractOembedProvider
{
    const URL_OEMBED = 'https://soundcloud.com/oembed?format=json&url=https://soundcloud.com/%s';
    const URL = 'https://soundcloud.com/%s';

    /**
     * @param FormMapper $formMapper
     */
    public function buildProviderEditFormBefore(FormMapper $formMapper)
    {
        $formMapper->add('providerReference', TextType::class, ['label' => $this->getReferenceLabel()]);
    }

    /**
     * @param FormMapper $formMapper
     */
    public function buildProviderEditFormAfter(FormMapper $formMapper)
    {
        $formMapper
            ->tab('form.embed_options')
            ->add(
                'providerMetaData',
                ImmutableArrayType::class,
                [
                    'keys' => [
                        ['autoPlay', CheckboxType::class, ['label' => 'form.auto_play', 'required' => false]],
                        ['hideRelated', CheckboxType::class, ['label' => 'form.hide_related', 'required' => false]],
                        ['showComments', CheckboxType::class, ['label' => 'form.show_comments', 'required' => false]],
                        ['showUser', CheckboxType::class, ['label' => 'form.show_user', 'required' => false]],
                        ['showReposts', CheckboxType::class, ['label' => 'form.show_reposts', 'required' => false]],
                        ['showVisual', CheckboxType::class, ['label' => 'form.show_visual', 'required' => false]],
                    ],
                    'label' => 'form.embed_options',
                    'required' => false
                ]
            )->end()
        ;
    }

    /**
     * @param string $id
     * @return string
     */
    public function getOembedUrl($id): string
    {
        return sprintf(self::URL_OEMBED, $id);
    }

    /**
     * @param $value
     * @return string
     * @throws \Exception
     */
    public function parseProviderReference($value): string
    {
        if (strpos($value, 'soundcloud.com')) {
            $url = parse_url($value);
            if (empty($url['path']) || empty(trim($url['path'], '/'))) {
                throw new InvalidProviderUrlException('SoundCloud');
            }

            return trim($url['path'], '/');
        }

        return $value;
    }

    /**
     * @param $id
     * @return array
     */
    protected function getOembedDataCache($id): array
    {
        $data = parent::getOembedDataCache($id);
        $data['embedUrl'] = $this->extractEmbedUrl($data);

        return $data;
    }

    /**
     * @param array $data
     * @return string
     */
    protected function extractEmbedUrl(array $data): string
    {
        preg_match('/src="(.*)"/', $data['html'], $matches);
        $url = $matches[1];

        $data = parse_url($url);
        parse_str($data['query'], $data);

        return $data['url'];
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return 'fa fa-soundcloud';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'soundcloud';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return AbstractProvider::TYPE_AUDIO;
    }
}
