import Site from './SiteApplication';

const app = new Site();

// @ts-expect-error We need to do this for backwards compatibility purposes.
window.app = app;

export default app;
