import { useState, useCallback } from 'react';

export type NotificationType = 'success' | 'error' | 'warning' | 'info';

export interface Notification {
  id: string;
  type: NotificationType;
  title: string;
  message?: string;
  duration?: number;
}

export function useNotifications() {
  const [notifications, setNotifications] = useState<Notification[]>([]);

  const removeNotification = useCallback((id: string) => {
    setNotifications((prev) =>
      prev.filter((notification) => notification.id !== id)
    );
  }, []);

  const addNotification = useCallback(
    (
      type: NotificationType,
      title: string,
      message?: string,
      duration: number = 5000
    ) => {
      const id = Date.now().toString();
      const notification: Notification = {
        id,
        type,
        title,
        message,
        duration,
      };

      setNotifications((prev) => [...prev, notification]);

      if (duration > 0) {
        setTimeout(() => {
          removeNotification(id);
        }, duration);
      }

      return id;
    },
    [removeNotification]
  );

  const clearAll = useCallback(() => {
    setNotifications([]);
  }, []);

  const success = useCallback(
    (title: string, message?: string, duration?: number) => {
      return addNotification('success', title, message, duration);
    },
    [addNotification]
  );

  const error = useCallback(
    (title: string, message?: string, duration?: number) => {
      return addNotification('error', title, message, duration);
    },
    [addNotification]
  );

  const warning = useCallback(
    (title: string, message?: string, duration?: number) => {
      return addNotification('warning', title, message, duration);
    },
    [addNotification]
  );

  const info = useCallback(
    (title: string, message?: string, duration?: number) => {
      return addNotification('info', title, message, duration);
    },
    [addNotification]
  );

  return {
    notifications,
    addNotification,
    removeNotification,
    clearAll,
    success,
    error,
    warning,
    info,
  };
}

export default useNotifications;
