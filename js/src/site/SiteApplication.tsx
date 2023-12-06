import app from './app';

import History from './utils/History';
import Pane from './utils/Pane';
import DiscussionPage from './components/DiscussionPage';
import SignUpModal from './components/SignUpModal';
import HeaderPrimary from './components/HeaderPrimary';
import HeaderSecondary from './components/HeaderSecondary';
import Composer from './components/Composer';
import DiscussionRenamedNotification from './components/DiscussionRenamedNotification';
import CommentPost from './components/CommentPost';
import DiscussionRenamedPost from './components/DiscussionRenamedPost';
import routes, { SiteRoutes, makeRouteHelpers } from './routes';
import alertEmailConfirmation from './utils/alertEmailConfirmation';
import Application, { ApplicationData } from '../common/Application';
import Navigation from '../common/components/Navigation';
import NotificationListState from './states/NotificationListState';
import GlobalSearchState from './states/GlobalSearchState';
import DiscussionListState from './states/DiscussionListState';
import ComposerState from './states/ComposerState';
import isSafariMobile from './utils/isSafariMobile';

import type Notification from './components/Notification';
import type Post from './components/Post';
import type Discussion from '../common/models/Discussion';
import type NotificationModel from '../common/models/Notification';
import type PostModel from '../common/models/Post';
import extractText from '../common/utils/extractText';

export interface SiteApplicationData extends ApplicationData {}

export default class SiteApplication extends Application {
  /**
   * 通知类型与其组件的映射。
   */
  notificationComponents: Record<string, ComponentClass<{ notification: NotificationModel }, Notification<{ notification: NotificationModel }>>> = {
    discussionRenamed: DiscussionRenamedNotification,
  };

  /**
   * 帖子类型与其组件的映射。
   */
  postComponents: Record<string, ComponentClass<{ post: PostModel }, Post<{ post: PostModel }>>> = {
    comment: CommentPost,
    discussionRenamed: DiscussionRenamedPost,
  };

  /**
   * 一个对象，用于控制页面侧窗格的状态。
   */
  pane: Pane | null = null;

  /**
   * 应用的历史记录堆栈，用于跟踪用户访问的路线，以便他们可以轻松地导航回上一条路线。
   */
  history: History = new History();

  /**
   * 一个对象，用于控制用户通知的状态。
   */
  notifications: NotificationListState = new NotificationListState();

  /**
   * 一个对象，用于存储以前搜索的查询，并提供用于检索和管理搜索值的便捷工具。
   */
  search: GlobalSearchState = new GlobalSearchState();

  /**
   * 一个对象，用于控制编写器的状态。
   */
  composer: ComposerState = new ComposerState();

  /**
   * 一个对象，用于控制缓存的讨论列表的状态，该列表在索引页和滑出窗格中使用。
   */
  discussions: DiscussionListState = new DiscussionListState({});

  route: typeof Application.prototype.route & SiteRoutes;

  data!: SiteApplicationData;

  constructor() {
    super();

    routes(this);

    this.route = Object.assign((Object.getPrototypeOf(Object.getPrototypeOf(this)) as Application).route.bind(this), makeRouteHelpers(this));
  }

  /**
   * @inheritdoc
   */
  mount() {
    // 获取配置的默认路由，并将该路由的路径更新为 '/' 。将主页作为第一条路线推送，这样用户就可以始终单击“返回”按钮回家，无论他们从哪个页面开始。
    const defaultRoute = this.site.attribute('defaultRoute');
    let defaultAction = 'index';

    for (const i in this.routes) {
      if (this.routes[i].path === defaultRoute) defaultAction = i;
    }

    this.routes[defaultAction].path = '/';
    this.history.push(defaultAction, extractText(this.translator.trans('core.site.header.back_to_index_tooltip')), '/');

    this.pane = new Pane(document.getElementById('app'));

    m.route.prefix = '';
    super.mount(this.site.attribute('basePath'));

    // 我们在页面之后挂载导航和标题组件，因此后退按钮等组件可以在渲染时访问更新的状态。
    m.mount(document.getElementById('app-navigation')!, { view: () => <Navigation className="App-backControl" drawer /> });
    m.mount(document.getElementById('header-navigation')!, Navigation);
    m.mount(document.getElementById('header-primary')!, HeaderPrimary);
    m.mount(document.getElementById('header-secondary')!, HeaderSecondary);
    m.mount(document.getElementById('composer')!, { view: () => <Composer state={this.composer} /> });

    alertEmailConfirmation(this);

    // 单击后将主页链接路由回主页。但是，如果用户在新选项卡中打开它，我们不希望它注册。
    document.getElementById('home-link')!.addEventListener('click', (e) => {
      if (e.ctrlKey || e.metaKey || e.button === 1) return;
      e.preventDefault();
      app.history.home();

      // 重新加载当前用户，以便刷新其未读通知计数。
      const userId = app.session.user?.id();
      if (userId) {
        app.store.find('users', userId);
        m.redraw();
      }
    });

    if (isSafariMobile()) {
      $(() => {
        $('.App').addClass('mobile-safari');
      });
    }
  }

  /**
   * 检查用户当前是否正在查看讨论。
   */
  public viewingDiscussion(discussion: Discussion): boolean {
    return this.current.matches(DiscussionPage, { discussion });
  }

  /**
   * 外部身份验证器（社交登录）操作完成时的回调。
   *
   * 如果有效负载指示用户已登录，则将重新加载页面。否则，将打开一个 SignUpModal，其中预填充了提供的详细信息。
   */
  public authenticationComplete(payload: Record<string, unknown>): void {
    if (payload.loggedIn) {
      window.location.reload();
    } else {
      this.modal.show(SignUpModal, payload);
    }
  }
}
