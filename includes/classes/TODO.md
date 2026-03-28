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
- `App\\Forum\\*` klasės turi likti `infusions/forum/classes/`.
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
- [ ] `App\\Content\\RevisionService`
- [ ] `App\\Content\\DraftPreviewService`
- [ ] `App\\Content\\TrashService`
- [ ] `App\\Content\\CommentService`
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
- [ ] Po to pereiti prie `NotificationCenter` ir `ProfileService`.
- [ ] Tik po to skaidyti tolimesnes pagalbines sritis i siauresnes paslaugas.
