import app from './app';

export { app };

// 导出公共 API

// 导出兼容 API
import compatObj from './compat';
import proxifyCompat from '../common/utils/proxifyCompat';

// @ts-expect-error `app` 实例需要在 compat 上可用。
compatObj.app = app;

export const compat = proxifyCompat(compatObj, 'admin');
