# Forum Classes TODO

## Paskirtis
- Moduliui priklausancios forumo klases, kurios neturi gyventi branduolio kataloge.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Etapai
- `v1.4` yra pagrindinis sio katalogo etapas.
- `v1.3` gali tik paruosti, kokios klases bus reikalingos.

## Esamos klases
- `ForumService.php`: temos ir atsakymai per `App\\Forum\\ForumService`.

## Likusieji darbai
- [x] `ForumService` perkeltas is `includes/classes/Forum/` i `infusions/forum/classes/`.
- [ ] Prideti `ThreadService`.
- [ ] Prideti `ReplyService`.
- [ ] Prideti `ModerationService`.
- [ ] Prideti `SearchService`.
- [ ] Prideti `ReadTrackingService`.
- [ ] Prideti `AttachmentService`.
- [ ] Prideti `ImageAttachmentService`.
- [ ] Prideti `VideoEmbedService`.
- [ ] Prideti `MentionService`.
- [ ] Prideti `ReactionService`.
- [ ] Prideti `ThanksService`.
- [ ] Prideti `SubscriptionService`.
- [ ] Prideti `BookmarkService`.
- [ ] Prideti `SolvedService`.
- [ ] Prideti `TagService`.
- [ ] Prideti `DraftService`.
- [ ] Prideti `PermissionService` per-forum teisiu logikai.
- [ ] Prideti `ModerationLogService`.
- [ ] Prideti `EditHistoryService`.
- [ ] Prideti `IpAuditService`.
- [ ] Prideti `RankPresenterService` forumo statusui ir badge'ams.
- [ ] Prideti `SeoService` forumo canonical ir structured data logikai.
- [ ] Vietoje vieno augancio `ForumService` pereiti prie siauresniu paslaugu.
