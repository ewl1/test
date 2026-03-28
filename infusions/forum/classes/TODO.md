# Forum Classes TODO

## Paskirtis
- Moduliui priklausančios forumo klasės, kurios neturėtų gyventi branduolio kataloge.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Etapai
- `v1.2` yra pagrindinis sio katalogo etapas.
- `v1.3` cia turetu atsirasti siauresni servisai vietoje vieno augancio serviso.

## Esamos klasės
- `ForumService.php`: temos ir atsakymai per `App\\Forum\\ForumService`.

## Likę darbai
- [x] `ForumService` perkeltas iš `includes/classes/Forum/` į `infusions/forum/classes/`.
- [ ] Pridėti atskiras klases moderavimui, paieškai ir forumo statistikai.
- [ ] Įvesti daugiau siaurų servisų vietoje vieno augančio `ForumService`.
