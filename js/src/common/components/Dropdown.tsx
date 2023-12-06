import app from '../../common/app';
import Component, { ComponentAttrs } from '../Component';
import icon from '../helpers/icon';
import listItems, { ModdedChildrenWithItemName } from '../helpers/listItems';
import extractText from '../utils/extractText';
import type Mithril from 'mithril';

export interface IDropdownAttrs extends ComponentAttrs {
  /** 要应用于下拉切换按钮的类名。 */
  buttonClassName?: string;
  /** 要应用于下拉菜单的类名。 */
  menuClassName?: string;
  /** 要在下拉切换按钮中显示的图标的名称。 */
  icon?: string;
  /** 要在按钮右侧显示的图标的名称。 */
  caretIcon?: string;
  /** 下拉切换按钮的标签。默认为“控件”。 */
  label: Mithril.Children;
  /** 用于向辅助读者描述下拉切换按钮的标签。默认为“切换下拉菜单”。 */
  accessibleToggleLabel?: string;
  /** 下拉列表折叠时要执行的操作。 */
  onhide?: () => void;
  /** 打开下拉列表时要执行的操作。 */
  onshow?: () => void;

  lazyDraw?: boolean;
}

/**
 * “下拉菜单”组件显示一个按钮，单击该按钮时，其下方会显示一个下拉菜单。
 *
 * 子项将在下拉菜单中显示为列表。
 */
export default class Dropdown<CustomAttrs extends IDropdownAttrs = IDropdownAttrs> extends Component<CustomAttrs> {
  protected showing = false;

  static initAttrs(attrs: IDropdownAttrs) {
    attrs.className ||= '';
    attrs.buttonClassName ||= '';
    attrs.menuClassName ||= '';
    attrs.label ||= '';
    attrs.caretIcon ??= 'fas fa-caret-down';
    attrs.accessibleToggleLabel ||= extractText(app.translator.trans('core.lib.dropdown.toggle_dropdown_accessible_label'));
  }

  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const items = vnode.children ? listItems(vnode.children as ModdedChildrenWithItemName[]) : [];
    const renderItems = this.attrs.lazyDraw ? this.showing : true;

    return (
      <div className={'ButtonGroup Dropdown dropdown ' + this.attrs.className + ' itemCount' + items.length + (this.showing ? ' open' : '')}>
        {this.getButton(vnode.children as Mithril.ChildArray)}
        {renderItems && this.getMenu(items)}
      </div>
    );
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    // 打开下拉菜单时，确定菜单是否超出视口底部。如果是这样，我们将应用类使其显示在切换按钮上方而不是下方。
    this.$().on('shown.bs.dropdown', () => {
      const { lazyDraw, onshow } = this.attrs;

      this.showing = true;

      // 如果使用惰性绘图，请在调用“onshow”函数之前重绘，以确保菜单 DOM 存在，以防回调尝试使用它。
      if (lazyDraw) {
        m.redraw.sync();
      }

      if (typeof onshow === 'function') {
        onshow();
      }

      // 如果不使用惰性绘制，则在调用 onshow（） 后保留以前的重绘功能
      if (!lazyDraw) {
        m.redraw();
      }

      const $menu = this.$('.Dropdown-menu');
      const isRight = $menu.hasClass('Dropdown-menu--right');

      const top = $menu.offset()?.top ?? 0;
      const height = $menu.height() ?? 0;
      const windowSrollTop = $(window).scrollTop() ?? 0;
      const windowHeight = $(window).height() ?? 0;

      $menu.removeClass('Dropdown-menu--top Dropdown-menu--right');

      $menu.toggleClass('Dropdown-menu--top', top + height > windowSrollTop + windowHeight);

      if (($menu.offset()?.top || 0) < 0) {
        $menu.removeClass('Dropdown-menu--top');
      }

      const left = $menu.offset()?.left ?? 0;
      const width = $menu.width() ?? 0;
      const windowScrollLeft = $(window).scrollLeft() ?? 0;
      const windowWidth = $(window).width() ?? 0;

      $menu.toggleClass('Dropdown-menu--right', isRight || left + width > windowScrollLeft + windowWidth);
    });

    this.$().on('hidden.bs.dropdown', () => {
      this.showing = false;

      if (this.attrs.onhide) {
        this.attrs.onhide();
      }

      m.redraw();
    });
  }

  /**
   * 获取按钮的模板。
   */
  getButton(children: Mithril.ChildArray): Mithril.Vnode<any, any> {
    return (
      <button
        className={'Dropdown-toggle ' + this.attrs.buttonClassName}
        aria-haspopup="menu"
        aria-label={this.attrs.accessibleToggleLabel}
        data-toggle="dropdown"
        onclick={this.attrs.onclick}
      >
        {this.getButtonContent(children)}
      </button>
    );
  }

  /**
   * 获取按钮内容的模板。
   */
  getButtonContent(children: Mithril.ChildArray): Mithril.ChildArray {
    return [
      this.attrs.icon ? icon(this.attrs.icon, { className: 'Button-icon' }) : '',
      <span className="Button-label">{this.attrs.label}</span>,
      this.attrs.caretIcon ? icon(this.attrs.caretIcon, { className: 'Button-caret' }) : '',
    ];
  }

  getMenu(items: Mithril.Vnode<any, any>[]): Mithril.Vnode<any, any> {
    return <ul className={'Dropdown-menu dropdown-menu ' + this.attrs.menuClassName}>{items}</ul>;
  }
}
