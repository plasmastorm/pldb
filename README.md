To set up the dev environment:

```bash
docker compose build
docker compose up
```

You can then log in with admin/admin at <http://localhost:8082/wp-admin>

This does everything except set the palette on the theme to match the live site, which can be done from:

`Appearance > Editor > Styles > Colours > Edit Palette` and choose `Evening` (the 2nd palette), then `Preview X items > Save`

(Doing this automatically would be painful as it involves maintaining a copy of some JSON which could change between theme updates)

To clear out the data and start afresh:

```bash
docker compose down
sudo rm database/ wpdb/ wordpress/ -r
```

...then set up as above
