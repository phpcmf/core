import app from '../../site/app';
import Page, { IPageAttrs } from '../../common/components/Page';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';
import DiscussionList from './DiscussionList';
import WelcomeHero from './WelcomeHero';
import DiscussionComposer from './DiscussionComposer';
import LogInModal from './LogInModal';
import DiscussionPage from './DiscussionPage';
import Dropdown from '../../common/components/Dropdown';
import Button from '../../common/components/Button';
import LinkButton from '../../common/components/LinkButton';
import SelectDropdown from '../../common/components/SelectDropdown';
import extractText from '../../common/utils/extractText';
import type Mithril from 'mithril';
import type Discussion from '../../common/models/Discussion';

export interface IIndexPageAttrs extends IPageAttrs {}

/**
 * `IndexPage` 组件显示索引页面，包括欢迎英雄、侧边栏和讨论列表。
 */
export default class IndexPage<CustomAttrs extends IIndexPageAttrs = IIndexPageAttrs, CustomState = {}> extends Page<CustomAttrs, CustomState> {
  static providesInitialSearch = true;
  lastDiscussion?: Discussion;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    // 如果用户从讨论页面返回，请记下他们刚刚访问了哪个讨论。呈现视图后，我们将向下滚动，以便查看此讨论。
    if (app.previous.matches(DiscussionPage)) {
      this.lastDiscussion = app.previous.get('discussion');
    }

    // 如果用户来自讨论列表，则他们要么刚刚切换了其中一个参数（筛选、排序、搜索），要么可能想要刷新结果。我们将清除讨论列表缓存，以便重新加载结果。
    if (app.previous.matches(IndexPage)) {
      app.discussions.clear();
    }

    app.discussions.refreshParams(app.search.params(), (m.route.param('page') && Number(m.route.param('page'))) || 1);

    app.history.push('index', extractText(app.translator.trans('core.site.header.back_to_index_tooltip')));

    this.bodyClass = 'App--index';
    this.scrollTopOnCreate = false;
  }

  view() {
    return (
      <div className="IndexPage">
        {this.hero()}
        <div className="container">
          <section className="sideNavContainer">

            <nav className="IndexPage-nav sideNav">
              <ul>{listItems(this.navbarItems().toArray())}</ul>
            </nav>

            <main className="IndexPage-results sideNavOffset">
              <div className="IndexPage-toolbar">
                <ul className="IndexPage-toolbar-view">{listItems(this.viewItems().toArray())}</ul>
                <ul className="IndexPage-toolbar-action">{listItems(this.actionItems().toArray())}</ul>
              </div>
              <DiscussionList state={app.discussions} />
            </main>

            <side class="IndexPage-side">
              <div><ul>{listItems(this.sidebarItems().toArray())}</ul></div>
              <div class="sidetext">
                <p>电工学</p>
                <span>与电工懂电的人员交流电工技术。</span>
              </div>
            </side>

          </section>
        </div>
      </div>
    );
  }

  setTitle() {
    app.setTitle(extractText(app.translator.trans('core.site.index.meta_title_text')));
    app.setTitleCount(0);
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    this.setTitle();

    // 计算出这个英雄的身高与前一个英雄的身高之间的差异。相对于英雄底部保持相同的滚动位置，这样侧边栏就不会跳来跳去。
    const oldHeroHeight = app.cache.heroHeight as number;
    const heroHeight = (app.cache.heroHeight = this.$('.Hero').outerHeight() || 0);
    const scrollTop = app.cache.scrollTop as number;

    $('#app').css('min-height', ($(window).height() || 0) + heroHeight);

    // 让浏览器处理页面重新加载时的滚动。
    if (app.previous.type == null) return;

    // 仅当我们来自讨论页面时才保留滚动位置。
    // 否则，我们只是更改了过滤器，因此我们应该转到页面顶部。
    if (this.lastDiscussion) {
      $(window).scrollTop(scrollTop - oldHeroHeight + heroHeight);
    } else {
      $(window).scrollTop(0);
    }

    // 如果我们刚刚从讨论页面返回，那么构造函数将设置 `lastDiscussion` 属性。如果是这种情况，我们希望向下滚动到该讨论，以便将其显示在视图中。
    if (this.lastDiscussion) {
      const $discussion = this.$(`li[data-id="${this.lastDiscussion.id()}"] .DiscussionListItem`);

      if ($discussion.length) {
        const indexTop = $('#header').outerHeight() || 0;
        const indexBottom = $(window).height() || 0;
        const discussionOffset = $discussion.offset();
        const discussionTop = (discussionOffset && discussionOffset.top) || 0;
        const discussionBottom = discussionTop + ($discussion.outerHeight() || 0);

        if (discussionTop < scrollTop + indexTop || discussionBottom > scrollTop + indexBottom) {
          $(window).scrollTop(discussionTop - indexTop);
        }
      }
    }
  }

  onbeforeremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onbeforeremove(vnode);

    // 保存滚动位置，以便我们在返回讨论列表时可以恢复它。
    app.cache.scrollTop = $(window).scrollTop();
  }

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    $('#app').css('min-height', '');
  }

  /**
   * 获取要显示为主图的组件。
   */
  hero() {
    return <WelcomeHero />;
  }

  sidebarItems() {
    const items = new ItemList<Mithril.Children>();
    const canStartDiscussion = app.site.attribute('canStartDiscussion') || !app.session.user;

    items.add(
      'newDiscussion',
      <Button
        icon="fas fa-edit"
        className="Button Button--primary IndexPage-newDiscussion"
        itemClassName="App-primaryControl"
        onclick={() => {
          // 如果用户未登录，则 promise 将拒绝，并显示登录模式。
          // 由于已经处理好了，我们不需要在控制台中显示错误消息。
          return this.newDiscussionAction().catch(() => {});
        }}
        disabled={!canStartDiscussion}
      >
        {app.translator.trans(`core.site.index.${canStartDiscussion ? 'start_discussion_button' : 'cannot_start_discussion_button'}`)}
      </Button>
    );

    return items;
  }

  /**
   * 为索引页的侧边栏构建项目列表。默认情况下，这是一个"New Discussion" 按钮，然后是一个包含导航项列表的 DropdownSelect 组件。
   */
  navbarItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'nav',
      <SelectDropdown
        buttonClassName="Button"
        className="App-titleControl"
        accessibleToggleLabel={app.translator.trans('core.site.index.toggle_sidenav_dropdown_accessible_label')}
      >
        {this.navItems().toArray()}
      </SelectDropdown>
    );

    return items;
  }

  // 在索引页的侧边栏中为导航生成项目列表。默认情况下，这只是 'All Discussions' 链接。
   
  navItems() {
    const items = new ItemList<Mithril.Children>();
    const params = app.search.stickyParams();

    items.add(
      'allDiscussions',
      <LinkButton href={app.route('index', params)} icon="far fa-comments">
        {app.translator.trans('core.site.index.all_discussions_link')}
      </LinkButton>,
      100
    );

    return items;
  }

  /**
   * 为工具栏的部分构建一个项目列表，该列表与结果的显示方式有关。默认情况下，这只是一个选择框，用于更改讨论的排序方式。
   */
  viewItems() {
    const items = new ItemList<Mithril.Children>();
    const sortMap = app.discussions.sortMap();

    const sortOptions = Object.keys(sortMap).reduce((acc: any, sortId) => {
      acc[sortId] = app.translator.trans(`core.site.index_sort.${sortId}_button`);
      return acc;
    }, {});

    items.add(
      'sort',
      <Dropdown
        buttonClassName="Button"
        label={sortOptions[app.search.params().sort] || Object.keys(sortMap).map((key) => sortOptions[key])[0]}
        accessibleToggleLabel={app.translator.trans('core.site.index_sort.toggle_dropdown_accessible_label')}
      >
        {Object.keys(sortOptions).map((value) => {
          const label = sortOptions[value];
          const active = (app.search.params().sort || Object.keys(sortMap)[0]) === value;

          return (
            <Button icon={active ? 'fas fa-check' : true} onclick={app.search.changeSort.bind(app.search, value)} active={active}>
              {label}
            </Button>
          );
        })}
      </Dropdown>
    );

    return items;
  }

  /**
   * 为工具栏的一部分构建一个项目列表，该部分是关于对结果执行操作的。默认情况下，这只是一个“全部标记为已读”按钮。
   */
  actionItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'refresh',
      <Button
        title={app.translator.trans('core.site.index.refresh_tooltip')}
        icon="fas fa-sync"
        className="Button Button--icon"
        onclick={() => {
          app.discussions.refresh();
          if (app.session.user) {
            app.store.find('users', app.session.user.id()!);
            m.redraw();
          }
        }}
      />
    );

    if (app.session.user) {
      items.add(
        'markAllAsRead',
        <Button
          title={app.translator.trans('core.site.index.mark_all_as_read_tooltip')}
          icon="fas fa-check"
          className="Button Button--icon"
          onclick={this.markAllAsRead.bind(this)}
        />
      );
    }

    return items;
  }

  /**
   * 打开编辑器进行新的讨论或提示用户登录。
   */
  newDiscussionAction(): Promise<unknown> {
    return new Promise((resolve, reject) => {
      if (app.session.user) {
        app.composer.load(DiscussionComposer, { user: app.session.user });
        app.composer.show();

        return resolve(app.composer);
      } else {
        app.modal.show(LogInModal);

        return reject();
      }
    });
  }

  /**
   * 将所有讨论标记为已读。
   */
  markAllAsRead() {
    const confirmation = confirm(extractText(app.translator.trans('core.site.index.mark_all_as_read_confirmation')));

    if (confirmation) {
      app.session.user?.save({ markedAllAsReadAt: new Date() });
    }
  }
}
