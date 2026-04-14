<?php

declare(strict_types=1);

namespace Richardhj\ContaoAjaxReloadElementBundle\EventListener;

use Contao\ArticleModel;
use Contao\ContentModel;
use Contao\Controller as ContaoController;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Image\PictureFactoryInterface;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\LayoutModel;
use Contao\Model;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Template;
use ReflectionClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsHook('parseTemplate')]
#[AsHook('getPageLayout', method: 'onGetPageLayout')]
class AjaxReloadElementListener
{
    private static bool $jsInjected = false;

    public const TYPE_MODULE = 'mod';
    public const TYPE_CONTENT = 'ce';
    public const TYPE_ARTICLE = 'art';

    public const ERROR_ELEMENT_NOT_FOUND = 1;
    public const ERROR_ELEMENT_AJAX_NOT_ALLOWED = 2;
    public const ERROR_ELEMENT_TYPE_UNKNOWN = 3;

    public function __construct(
        private readonly PictureFactoryInterface $pictureFactory,
        private readonly RequestStack $requestStack,
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
        private readonly InsertTagParser $insertTagParser,
    ) {
    }

    public function onParseTemplate(Template $template): void
    {
        if (!($template instanceof FrontendTemplate) || !(bool) $template->allowAjaxReload) {
            return;
        }

        $template->cssID = (string) $template->cssID . sprintf(
            ' data-ajax-reload-element="%s::%u"%s data-ajax-reload-token="%s"',
            $this->determineTemplateType($template),
            (int) $template->id,
            $template->ajaxReloadFormSubmit ? ' data-ajax-reload-form-submit=""' : '',
            htmlspecialchars($this->csrfTokenManager->getDefaultTokenValue(), ENT_QUOTES)
        );

        if (!self::$jsInjected) {
            $template = new FrontendTemplate('j_ajax_reload_pagination');
            $GLOBALS['TL_BODY'][] = $template->parse();
            self::$jsInjected = true;
        }
    }

    public function onGetPageLayout(PageModel $page, LayoutModel $layout): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$request->isXmlHttpRequest()) {
            return;
        }

        $paramElement = $this->getRequestedElement($request);

        if (null === $paramElement) {
            return;
        }

        [$elementType, $elementId] = array_pad(StringUtil::trimsplit('::', $paramElement), 2, null);

        if (null === $elementType || null === $elementId || !is_numeric($elementId)) {
            $this->terminateWithError(self::ERROR_ELEMENT_TYPE_UNKNOWN);
        }

        $elementId = (int) $elementId;
        $this->prepareRequestForReload($request, $elementType, $elementId);

        [$element, $elementParser] = match ($elementType) {
            self::TYPE_MODULE => [ModuleModel::findByPk($elementId), [ContaoController::class, 'getFrontendModule']],
            self::TYPE_CONTENT => [ContentModel::findByPk($elementId), [ContaoController::class, 'getContentElement']],
            self::TYPE_ARTICLE => [ArticleModel::findByPk($elementId), [ContaoController::class, 'getArticle']],
            default => $this->terminateWithError(self::ERROR_ELEMENT_TYPE_UNKNOWN),
        };

        $this->ensureModelIsNotNull($element, $paramElement);
        $this->ensureAjaxReloadIsAllowed($element);

        unset($_SESSION['LOGIN_ERROR']);

        $theme = $layout->getRelated('pid');

        if ($theme && isset($theme->defaultImageDensities) && '' !== (string) $theme->defaultImageDensities) {
            $this->pictureFactory->setDefaultDensities($theme->defaultImageDensities);
        }

        $page->layoutId = $layout->id;
        $page->template = $layout->template ?: 'fe_page';

        if ($theme && isset($theme->templates) && $theme->templates) {
            $page->templateGroup = $theme->templates;
        }

        [$format, $variant] = array_pad(explode('_', (string) $layout->doctype, 2), 2, null);
        $page->outputFormat = $format;
        $page->outputVariant = $variant;

        $html = $elementParser($element);
        $html = $this->insertTagParser->replace($html);
        $html = str_replace(
            ['{{request_token}}', '[{]', '[}]'],
            [$this->csrfTokenManager->getDefaultTokenValue(), '{{', '}}'],
            $html
        );

        if (method_exists(ContaoController::class, 'replaceDynamicScriptTags')) {
            $html = ContaoController::replaceDynamicScriptTags($html);
        }

        $response = new JsonResponse([
            'status' => 'ok',
            'html' => $html,
        ]);
        $response->send();
        exit;
    }

    private function determineTemplateType(FrontendTemplate $template): string
    {
        if ('article' === (string) $template->type) {
            return self::TYPE_ARTICLE;
        }

        return \in_array((string) ($template->ptable ?? ''), ['tl_article', 'tl_news', 'tl_calendar_events'], true)
            ? self::TYPE_CONTENT
            : self::TYPE_MODULE;
    }

    private function getRequestedElement(Request $request): ?string
    {
        $element = $request->query->all()['ajax_reload_element'] ?? null;

        if (\is_string($element) && '' !== $element) {
            return $element;
        }

        $element = $request->request->all()['ajax_reload_element'] ?? null;

        return \is_string($element) && '' !== $element ? $element : null;
    }

    private function prepareRequestForReload(Request $request, string $elementType, int $elementId): void
    {
        $queryParameters = $request->query->all();
        unset($queryParameters['ajax_reload_element']);
        $request->query->replace($queryParameters);

        $requestParameters = $request->request->all();
        unset($requestParameters['ajax_reload_element']);
        $request->request->replace($requestParameters);

        $inputAttributes = $request->attributes->get('_contao_input', []);
        $setGet = $inputAttributes['setGet'] ?? [];
        $setGet['ajax_reload_element'] = null;

        $pageParameterName = $this->getPageParameterName($elementType, $elementId);
        $genericPage = $queryParameters['page'] ?? null;

        if (
            null !== $genericPage
            && null !== $pageParameterName
            && !array_key_exists($pageParameterName, $queryParameters)
        ) {
            $setGet[$pageParameterName] = $genericPage;
        }

        $inputAttributes['setGet'] = $setGet;
        $request->attributes->set('_contao_input', $inputAttributes);

        Environment::set('request', $this->buildRelativeRequest($request, $queryParameters));
    }

    private function getPageParameterName(string $elementType, int $elementId): ?string
    {
        return match ($elementType) {
            self::TYPE_MODULE => 'page_n'.$elementId,
            self::TYPE_CONTENT => 'page_c'.$elementId,
            self::TYPE_ARTICLE => 'page_a'.$elementId,
            default => null,
        };
    }

    private function buildRelativeRequest(Request $request, array $queryParameters): string
    {
        $relativeRequest = ltrim($request->getPathInfo(), '/');
        $queryString = http_build_query($queryParameters);

        return '' === $queryString ? $relativeRequest : $relativeRequest.'?'.$queryString;
    }

    private function ensureModelIsNotNull(Model|null $model, string $modelIdentifier): void
    {
        if (null !== $model) {
            return;
        }

        $this->terminateWithError(self::ERROR_ELEMENT_NOT_FOUND, $modelIdentifier);
    }

    private function ensureAjaxReloadIsAllowed(Model $model): void
    {
        if ((bool) $model->allowAjaxReload) {
            return;
        }

        $elementType = (new ReflectionClass($model))->getShortName();
        $elementType = substr($elementType, 0, -5);

        $this->terminateWithError(self::ERROR_ELEMENT_AJAX_NOT_ALLOWED, [$elementType, $model->id]);
    }

    private function terminateWithError(int $errorCode, array|string $args = []): never
    {
        $response = new JsonResponse([
            'status' => 'error',
            'error_code' => $errorCode,
            'error' => vsprintf($GLOBALS['TL_LANG']['ERR']['ajaxReloadElement'][$errorCode], (array) $args),
        ]);
        $response->send();
        exit;
    }
}
