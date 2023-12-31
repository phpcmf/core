import type Mithril from 'mithril';
import classList from '../utils/classList';

/**
 * The `icon` helper displays an icon.
 *
 * @param fontClass The full icon class, prefix and the icon’s name.
 * @param attrs Any other attributes to apply.
 */
export default function icon(fontClass: string, attrs: Mithril.Attributes = {}): Mithril.Vnode {
  attrs.className = classList('icon', fontClass, attrs.className);

  return <i aria-hidden="true" {...attrs} />;
}
