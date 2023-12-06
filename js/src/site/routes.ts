import SiteApplication from './SiteApplication';
import IndexPage from './components/IndexPage';
import DiscussionPage from './components/DiscussionPage';
import PostsUserPage from './components/PostsUserPage';
import DiscussionsUserPage from './components/DiscussionsUserPage';
import SettingsPage from './components/SettingsPage';
import NotificationsPage from './components/NotificationsPage';
import DiscussionPageResolver from './resolvers/DiscussionPageResolver';
import Discussion from '../common/models/Discussion';
import type Post from '../common/models/Post';
import type User from '../common/models/User';
import UserSecurityPage from './components/UserSecurityPage';

/**
 * Helper functions to generate URLs to form pages.
 */
export interface SiteRoutes {
  discussion: (discussion: Discussion, near?: number) => string;
  post: (post: Post) => string;
  user: (user: User) => string;
}

/**
 * The `routes` initializer defines the site app's routes.
 */
export default function (app: SiteApplication) {
  app.routes = {
    index: { path: '/all', component: IndexPage },

    discussion: { path: '/discussion/:id', component: DiscussionPage, resolverClass: DiscussionPageResolver },
    'discussion.near': { path: '/discussion/:id/:near', component: DiscussionPage, resolverClass: DiscussionPageResolver },

    user: { path: '/user/:username', component: PostsUserPage },
    'user.posts': { path: '/user/:username', component: PostsUserPage },
    'user.discussions': { path: '/user/:username/discussions', component: DiscussionsUserPage },

    settings: { path: '/settings', component: SettingsPage },
    'user.security': { path: '/user/:username/security', component: UserSecurityPage },
    notifications: { path: '/notifications', component: NotificationsPage },
  };
}

export function makeRouteHelpers(app: SiteApplication) {
  return {
    /**
     * Generate a URL to a discussion.
     */
    discussion: (discussion: Discussion, near?: number) => {
      return app.route(near && near !== 1 ? 'discussion.near' : 'discussion', {
        id: discussion.slug(),
        near: near && near !== 1 ? near : undefined,
      });
    },

    /**
     * Generate a URL to a post.
     */
    post: (post: Post) => {
      return app.route.discussion(post.discussion(), post.number());
    },

    /**
     * Generate a URL to a user.
     */
    user: (user: User) => {
      return app.route('user', {
        username: user.slug(),
      });
    },
  };
}
