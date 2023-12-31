import app from '../../admin/app';

export default function saveSettings(settings) {
  const oldSettings = JSON.parse(JSON.stringify(app.data.settings));

  Object.assign(app.data.settings, settings);

  return app
    .request({
      method: 'POST',
      url: app.site.attribute('apiUrl') + '/settings',
      body: settings,
    })
    .catch((error) => {
      app.data.settings = oldSettings;
      throw error;
    });
}
