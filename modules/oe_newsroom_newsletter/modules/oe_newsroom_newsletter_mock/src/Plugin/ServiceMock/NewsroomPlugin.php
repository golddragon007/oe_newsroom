<?php

declare(strict_types = 1);

namespace Drupal\oe_newsroom_newsletter_mock\Plugin\ServiceMock;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\State\State;
use Drupal\http_request_mock\ServiceMockPluginInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Intercepts any HTTP request made to Newsroom.
 *
 * @ServiceMock(
 *   id = "newsroom",
 *   label = @Translation("Newsroom mock"),
 *   weight = 0,
 * )
 */
class NewsroomPlugin extends PluginBase implements ServiceMockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The key of the state entry that contains the mocked api subscriptions.
   */
  public const STATE_KEY_SUBSCRIPTIONS = 'oe_newsroom.mock_api_subscriptions';

  /**
   * The key of the state entry that contains the mocked api universe.
   */
  public const STATE_KEY_UNIVERSE = 'oe_newsroom.mock_api_universe';

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, State $state) {
    $this->state = $state;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RequestInterface $request, array $options): bool {
    return $request->getUri()->getHost() === 'ec.europa.eu' && strpos($request->getUri()->getPath(), '/newsroom/api/v1/') === 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(RequestInterface $request, array $options): ResponseInterface {
    switch ($request->getUri()->getPath()) {
      case '/newsroom/api/v1/subscriptions':
        return $this->subscriptions($request);

      case '/newsroom/api/v1/subscribe':
        return $this->subscribe($request);

      case '/newsroom/api/v1/unsubscribe':
        return $this->unsubscribe($request);
    }

    return new Response(404);
  }

  /**
   * Generates a Newsroom subscription array.
   *
   * @param string $universe
   *   Subscription list universe.
   * @param string $email
   *   Subscribed email address.
   * @param string $sv_id
   *   Distribution list ID.
   * @param string $language_code
   *   Language where the subscription was made.
   * @param bool $isNewSubscription
   *   Is it a new subscription?
   *
   * @return array
   *   Generated subscription array, similar to newsrooms one.
   */
  protected function generateSubscriptionArray(string $universe, string $email, string $sv_id, string $language_code, bool $isNewSubscription): array {
    // These are here google translations, but it will do the job to simulate
    // the original behaviour.
    $new_subscription = [
      'bg' => '???????????????????? ????, ???? ?????? ???? ???????????????????????? ???? ????????????????: ???????????? ???? ???????????????? ???? ??????????????',
      'cs' => 'D??kujeme, ??e jste se zaregistrovali do slu??by: Testovac?? slu??ba zpravodaje',
      'da' => 'Tak fordi du tilmeldte dig tjenesten: Test nyhedsbrevsservice',
      'de' => 'Vielen Dank f??r Ihre Anmeldung zum Service: Test Newsletter Service',
      'et' => 'T??name, et registreerusite teenusesse: testige uudiskirja teenust',
      'el' => '???????????????????????? ?????? ?????? ?????????????? ?????? ???????? ????????????????: ???????????? ?????????????????? ???????????????????????? ??????????????',
      'en' => 'Thanks for Signing Up to the service: Test Newsletter Service',
      'es' => 'Gracias por suscribirse al servicio: Test Newsletter Service',
      'fr' => 'Merci de vous ??tre inscrit au service??: Testez le service de newsletter',
      'ga' => 'Go raibh maith agat as S??ni?? leis an tseirbh??s: Seirbh??s Nuachtlitir T??st??la',
      'hr' => 'Hvala vam ??to ste se prijavili za uslugu: Test Newsletter Service',
      'it' => 'Grazie per esserti iscritto al servizio: Test Newsletter Service',
      'lv' => 'Paldies, ka re??istr??j??ties pakalpojumam: P??rbaudiet bi??etenu pakalpojumu',
      'lt' => 'D??kojame, kad prisiregistravote prie paslaugos: i??bandykite naujienlai??kio paslaug??',
      'hu' => 'K??sz??nj??k, hogy feliratkozott a szolg??ltat??sra: Teszt h??rlev??l szolg??ltat??s',
      'mt' => 'Grazzi talli rre??istrajt g??as-servizz: Test Newsletter Service',
      'nl' => 'Bedankt voor het aanmelden voor de service: Test nieuwsbriefservice',
      'pl' => 'Dzi??kujemy za zapisanie si?? do us??ugi: Testowa us??uga Newsletter',
      'pt' => 'Obrigado por se inscrever no servi??o: Servi??o de boletim informativo de teste',
      'ro' => 'V?? mul??umim c?? v-a??i ??nscris la serviciu: serviciul Newsletter Test',
      'sk' => '??akujeme, ??e ste sa zaregistrovali do slu??by: Slu??ba testovania spravodajcov',
      'sl' => 'Hvala za prijavo na storitev: Test Newsletter Service',
      'fi' => 'Kiitos rekister??itymisest?? palveluun: Testaa uutiskirjepalvelu',
      'sv' => 'Tack f??r att du anm??ler dig till tj??nsten: Testa nyhetsbrevstj??nsten',
    ];

    $already_subscribed = [
      'bg' => '???? ???????? ?????????? ?????????? ???????? ?? ?????????????????????? ?????????????????? ???? ???????? ????????????',
      'cs' => 'Pro tuto e -mailovou adresu je ji?? zaregistrov??no p??edplatn?? t??to slu??by',
      'da' => 'Et abonnement p?? denne service er allerede registreret for denne e -mail -adresse',
      'de' => 'F??r diese E-Mail-Adresse ist bereits ein Abonnement f??r diesen Dienst registriert',
      'et' => 'Selle e -posti aadressi jaoks on selle teenuse tellimus juba registreeritud',
      'el' => '?????? ???????????????? ???? ?????????? ?????? ???????????????? ???????? ?????? ?????????????????????? ?????? ?????????? ???? ?????????????????? ???????????????????????? ????????????????????????',
      'en' => 'A subscription for this service is already registered for this email address',
      'es' => 'Ya se ha registrado una suscripci??n a este servicio para esta direcci??n de correo electr??nico',
      'fr' => 'Un abonnement ?? ce service est d??j?? enregistr?? pour cette adresse e-mail',
      'ga' => 'T?? s??nti??s leis an tseirbh??s seo cl??raithe cheana f??in don seoladh r??omhphoist seo',
      'hr' => 'Pretplata na ovu uslugu ve?? je registrirana za ovu adresu e -po??te',
      'it' => 'Un abbonamento a questo servizio ?? gi?? registrato per questo indirizzo email',
      'lv' => '??im e -pasta adresei jau ir re??istr??ts ???? pakalpojuma abonements',
      'lt' => '??iam el. Pa??to adresui jau yra u??registruota ??ios paslaugos prenumerata',
      'hu' => 'A szolg??ltat??s el??fizet??se m??r regisztr??lva van erre az e -mail c??mre',
      'mt' => 'Abbonament g??al dan is-servizz huwa di???? rre??istrat g??al dan l-indirizz elettroniku',
      'nl' => 'Er is al een abonnement op deze service geregistreerd voor dit e-mailadres',
      'pl' => 'Subskrypcja tej us??ugi jest ju?? zarejestrowana dla tego adresu e-mail',
      'pt' => 'Uma assinatura deste servi??o j?? est?? registrada para este endere??o de e-mail',
      'ro' => 'Un abonament la acest serviciu este deja ??nregistrat pentru aceast?? adres?? de e-mail',
      'sk' => 'Na t??to e -mailov?? adresu je u?? zaregistrovan?? predplatn?? tejto slu??by',
      'sl' => 'Za ta e -po??tni naslov je ??e registrirana naro??nina na to storitev',
      'fi' => 'Palvelun tilaus on jo rekister??ity t??h??n s??hk??postiosoitteeseen',
      'sv' => 'En prenumeration p?? denna tj??nst ??r redan registrerad f??r denna e -postadress',
    ];

    return [
      'responseType' => 'json',
      'email' => $email,
      'firstName' => NULL,
      'lastName' => NULL,
      'organisation' => NULL,
      'country' => NULL,
      'position' => NULL,
      'twitter' => NULL,
      'facebook' => NULL,
      'linkedIn' => NULL,
      'phone' => NULL,
      'organisationShort' => NULL,
      'address' => NULL,
      'address2' => NULL,
      'postCode' => NULL,
      'city' => NULL,
      'department' => NULL,
      'media' => NULL,
      'website' => NULL,
      'role' => NULL,
      'universeId' => '1',
      'universeName' => 'TEST FORUM',
      'universAcronym' => $universe,
      'newsletterId' => $sv_id,
      'newsletterName' => 'Test newsletter distribution list',
      'status' => 'Valid',
      'unsubscriptionLink' => "https://ec.europa.eu/newsroom/$universe/user-subscriptions/unsubscribe/$email/RANDOM_STRING",
      'isNewUser' => NULL,
      'hostBy' => "$universe Newsroom",
      'profileLink' => "https://ec.europa.eu/newsroom/$universe/user-profile/123456789",
      'isNewSubscription' => $isNewSubscription,
      'feedbackMessage' => $isNewSubscription ? $new_subscription[$language_code] ?? $new_subscription['en'] : $already_subscribed[$language_code] ?? $already_subscribed['en'],
      'language' => $language_code,
      'frequency' => 'On demand',
      'defaultLanguage' => '0',
      'pattern' => NULL,
    ];
  }

  /**
   * Fetching subscription(s).
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   Http request. If sv_id is provided, it will act as a filter. This
   *   function assumes that the provided sv_id is part of the same universe as
   *   it was queried (unmatching universe and sv_ids will generate an exception
   *   in the real API).
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   Http response.
   */
  protected function subscriptions(RequestInterface $request): ResponseInterface {
    $data = Json::decode($request->getBody()->getContents());
    $universe = $data['subscription']['universeAcronym'];
    $email = $data['subscription']['email'];
    $svIds = explode(',', $data['subscription']['sv_id']);

    $subscriptions = $this->state->get(self::STATE_KEY_SUBSCRIPTIONS, []);
    $current_subs = [];
    // If sv_id is present, then it used as a filter.
    if (count($svIds) > 0) {
      foreach ($svIds as $svId) {
        if (isset($subscriptions[$universe][$svId][$email]) && $subscriptions[$universe][$svId][$email]['subscribed']) {
          $current_subs[] = $this->generateSubscriptionArray($universe, $email, $svId, $subscriptions[$universe][$svId][$email]['language'], FALSE);
        }
      }
    }
    // If not present, then display all results.
    else {
      foreach ($subscriptions[$universe] as $svId => $subscription) {
        if (isset($subscription[$email]) && $subscription[$email]['subscribed']) {
          $current_subs[] = $this->generateSubscriptionArray($universe, $email, $svId, $subscription[$email]['language'], FALSE);
        }
      }
    }

    return new Response(200, [], Json::encode($current_subs));
  }

  /**
   * Generates a subscription.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   Http request.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   Http response.
   */
  protected function subscribe(RequestInterface $request): ResponseInterface {
    $data = Json::decode($request->getBody()->getContents());
    $universe = $data['subscription']['universeAcronym'];
    $app = $data['subscription']['topicExtWebsite'];
    $email = $data['subscription']['email'];
    $svIds = explode(',', $data['subscription']['sv_id']);
    $relatedSvIds = isset($data['subscription']['relatedSv_Id']) ? explode(',', $data['subscription']['relatedSv_Id']) : [];
    $language = $data['subscription']['language'] ?? 'en';
    $topicExtId = isset($data['subscription']['topicExtId']) ? explode(',', $data['subscription']['topicExtId']) : [];

    $subscriptions = $this->state->get(self::STATE_KEY_SUBSCRIPTIONS, []);
    $universes = $this->state->get(self::STATE_KEY_UNIVERSE, []);
    $current_subs = [];
    foreach (array_merge($svIds, $relatedSvIds) as $svId) {
      // Select the first to returned as the normal API class, but the
      // webservice marks all as subscribed, so let's mark it here too.
      if (empty($subscriptions[$universe][$svId][$email]['subscribed'])) {
        $current_subs[] = $this->generateSubscriptionArray($universe, $email, $svId, $language, TRUE);
      }
      else {
        $current_subs[] = $this->generateSubscriptionArray($universe, $email, $svId, $language, FALSE);
      }

      $universes[$app] = $universe;

      $subscriptions[$universe][$svId][$email] = [
        'subscribed' => TRUE,
        'language' => $language,
        'topicExtId' => $topicExtId,
      ];
    }
    $this->state->set(self::STATE_KEY_SUBSCRIPTIONS, $subscriptions);
    $this->state->set(self::STATE_KEY_UNIVERSE, $universes);

    return new Response(200, [], Json::encode($current_subs));
  }

  /**
   * Generates an unsubscription.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   Http request.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   Http response.
   */
  protected function unsubscribe(RequestInterface $request): ResponseInterface {
    parse_str($request->getUri()->getQuery(), $parameters);

    $email = $parameters['user_email'];
    // The API does not support multiple sv_ids in a single call.
    $svId = $parameters['sv_id'];

    $subscriptions = $this->state->get(self::STATE_KEY_SUBSCRIPTIONS, []);
    $universes = $this->state->get(self::STATE_KEY_UNIVERSE, []);
    $universe = $universes[$parameters['app']];

    // When you try to unsubscribe a user that newsroom does not have at all,
    // you will get an internal error which will converted by our API to this.
    if (!isset($subscriptions[$universe][$svId][$email])) {
      return new Response(404, [], 'Not found');
    }

    // When the user e-mail exists in the e-mail it will return the same message
    // regardless if it's subscribed or not previously.
    $subscriptions[$universe][$svId][$email] = [
      'subscribed' => FALSE,
      'language' => NULL,
      'topicExtId' => NULL,
    ];

    $this->state->set(self::STATE_KEY_SUBSCRIPTIONS, $subscriptions);

    return new Response(200, [], 'User unsubscribed!');
  }

}
