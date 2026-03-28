# Classes TODO

## Paskirtis
- Vieta branduolio klasems ir PSR-4 stiliaus paslaugoms, kurios neturi gyventi modulio kataloguose.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Etapai
- `v1.1`: tik paruosiamieji darbai ir namespace ribos.
- `v1.2`: pirmosios branduolio paslaugos.
- `v1.4`: pilnesni registrai, update ir system service sluoksnis.
- `v1.5`: SEO ir performance paslaugos.

## Architekturos ribos
- Moduliu klases neturi gyventi `includes/classes/`.
- `App\\Forum\\*` klases turi likti `infusions/forum/classes/`.
- Naujienu, shoutbox ir kitu moduliu klasems galioja ta pati taisykle.

## Planuojamos branduolio klases

### Auth ir saugumas
- [ ] `App\\Auth\\AuthService`
- [ ] `App\\Security\\SecurityHeadersManager`
- [ ] `App\\Security\\LoginAlertService`
- [ ] `App\\Security\\SuspiciousActivityDetector`
- [ ] `App\\Security\\PasswordResetAuditService`
- [ ] `App\\Security\\UploadQuarantineService`

### Mail, komunikacija ir pranesimai
- [ ] `App\\Mail\\Mailer`
- [ ] `App\\Messaging\\ConversationService`
- [ ] `App\\Messaging\\MessageService`
- [ ] `App\\Messaging\\BlockListService`
- [ ] `App\\Notifications\\NotificationCenter`
- [ ] `App\\Notifications\\NotificationPreferenceService`
- [ ] `App\\Notifications\\NotificationDeliveryService`
- [ ] `App\\Notifications\\NotificationTemplateResolver`
- [ ] `App\\Notifications\\ActivityEventService`
- [ ] `App\\Notifications\\ActivityFeedQueryService`

### Profilis ir naudotojo paskyra
- [ ] `App\\Profile\\ProfileService`
- [ ] `App\\Profile\\PrivacyService`
- [ ] `App\\Profile\\SecuritySettingsService`
- [ ] `App\\Profile\\ActivityFeedService`

### Sistema ir atnaujinimai
- [ ] `App\\Cache\\CacheStore`
- [ ] `App\\System\\SettingsRegistry`
- [ ] `App\\System\\FeatureFlagService`
- [ ] `App\\System\\MaintenanceScheduler`
- [ ] `App\\System\\UpdateManager`
- [ ] `App\\System\\PluginConflictDetector`
- [ ] `App\\System\\ErrorHandler`
- [ ] `App\\System\\RequestLogger`
- [ ] `App\\System\\CronRegistry`
- [ ] `App\\System\\HookDebugger`
- [ ] `App\\System\\DependencyGraphService`

### Turinys ir paieska
- [ ] `App\\Search\\SearchIndexService`
- [ ] `App\\Search\\SearchProviderRegistry`
- [ ] `App\\Search\\SearchQueryService`
- [ ] `App\\Search\\SearchRanker`
- [ ] `App\\Search\\SearchHighlighter`
- [ ] `App\\Search\\SearchSuggestionService`
- [ ] `App\\Search\\SearchAnalyticsService`
- [ ] `App\\Search\\SearchReindexService`
- [ ] `App\\Search\\SearchPermissionFilter`
- [ ] `App\\Content\\RevisionService`
- [ ] `App\\Content\\DraftPreviewService`
- [ ] `App\\Content\\TrashService`
- [ ] `App\\Content\\CommentService`
- [ ] `App\\Content\\CommentThreadService`
- [ ] `App\\Content\\CommentModerationService`
- [ ] `App\\Content\\CommentNotificationService`
- [ ] `App\\Content\\CommentRenderer`
- [ ] `App\\Content\\CommentPolicyService`
- [ ] `App\\Content\\EmbedService`
- [ ] `App\\Content\\YoutubeEmbedService`
- [ ] `App\\Content\\MediaUploadPolicyService`
- [ ] `App\\Content\\ImageAttachmentService`
- [ ] `App\\Content\\ReactionService`
- [ ] `App\\Content\\BookmarkService`
- [ ] `App\\Content\\ReportService`
- [ ] `App\\Content\\ModerationQueueService`
- [ ] `App\\Content\\ReputationService`
- [ ] `App\\Content\\BadgeService`
- [ ] `App\\Content\\PollService`

### Performance ir SEO
- [ ] `App\\Performance\\PageCacheService`
- [ ] `App\\Performance\\PanelCacheService`
- [ ] `App\\Performance\\QueryProfiler`
- [ ] `App\\Performance\\AssetPipeline`
- [ ] `App\\Performance\\ImageOptimizer`
- [ ] `App\\Seo\\SeoManager`
- [ ] `App\\Seo\\SchemaBuilder`
- [ ] `App\\Seo\\RedirectManager`
- [ ] `App\\Seo\\BrokenLinkScanner`

## Likusieji darbai
- [x] Paruosta `composer.json` autoload sekcija (`psr-4`) su `App\\`.
- [x] `App\\Forum\\ForumService` iskeltas is branduolio i forumo moduli.
- [ ] Pradeti nuo `App\\Auth\\AuthService`, `App\\Mail\\Mailer` ir `App\\Cache\\CacheStore`.
- [ ] Po to pereiti prie `NotificationCenter`, activity feed sluoksnio ir `ProfileService`.
- [ ] Po pagrindiniu paslaugu pradeti bendro `comments engine` klasiu rinkini, kad naujienos ir profiliai nebesilaikytu ant atskiros logikos.
- [ ] Tuo paciu sluoksniu pradeti ir bendra paieskos klasiu rinkini, kad `search.php` netaptu vieninteliu paieskos centru.
- [ ] Kartu su bendru turinio sluoksniu pradeti media/embed klases, kad forumas ir naujienos nenaudotu skirtingu taisykliu.
- [ ] Tik po to skaidyti tolimesnes pagalbines sritis i siauresnes paslaugas.
