import type { Notification, NotificationType } from '../hooks/useNotifications';

let globalNotifications: Notification[] = [];
let globalListeners: Array<(notifications: Notification[]) => void> = [];

export const notificationManager = {
  add: (
    type: NotificationType,
    title: string,
    message?: string,
    duration: number = 5000
  ) => {
    const id = Date.now().toString();
    const notification: Notification = { id, type, title, message, duration };

    globalNotifications = [...globalNotifications, notification];
    globalListeners.forEach((listener) => listener(globalNotifications));

    if (duration > 0) {
      setTimeout(() => {
        notificationManager.remove(id);
      }, duration);
    }

    return id;
  },

  remove: (id: string) => {
    globalNotifications = globalNotifications.filter((n) => n.id !== id);
    globalListeners.forEach((listener) => listener(globalNotifications));
  },

  subscribe: (listener: (notifications: Notification[]) => void) => {
    globalListeners.push(listener);
    return () => {
      globalListeners = globalListeners.filter((l) => l !== listener);
    };
  },

  success: (title: string, message?: string, duration?: number) =>
    notificationManager.add('success', title, message, duration),

  error: (title: string, message?: string, duration?: number) =>
    notificationManager.add('error', title, message, duration),

  warning: (title: string, message?: string, duration?: number) =>
    notificationManager.add('warning', title, message, duration),

  info: (title: string, message?: string, duration?: number) =>
    notificationManager.add('info', title, message, duration),
};
