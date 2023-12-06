import app from '../../admin/app';
import Component from '../../common/Component';
import LinkButton from '../../common/components/LinkButton';
import SessionDropdown from './SessionDropdown';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';

/**
 * 这是 `HeaderSecondary` 组件显示辅助标头控件。
 */
export default class HeaderSecondary extends Component {
  view() {
    return <ul className="Header-controls">{listItems(this.items().toArray())}</ul>;
  }

  /**
   * 为控件生成项列表。
   *
   * @return {ItemList<import('mithril').Children>}
   */
  items() {
    const items = new ItemList();

    items.add(
      'help',
      <LinkButton href="https://phpcmf.cn/docs" icon="fas fa-question-circle" external={true} target="_blank">
        {app.translator.trans('core.admin.header.get_help')}
      </LinkButton>
    );

    items.add('session', <SessionDropdown />);

    return items;
  }
}
