import { useEffect, useState } from 'react';
import type { Notification } from '../hooks/useNotifications';
import { notificationManager } from '../utils/notificationManager';

interface NotificationContainerProps {
  notifications?: Notification[];
  onRemove?: (id: string) => void;
}

function NotificationItem({
  notification,
  onRemove,
}: {
  notification: Notification;
  onRemove: (id: string) => void;
}) {
  const { id, type, title, message } = notification;

  const getTypeStyles = () => {
    switch (type) {
      case 'success':
        return 'bg-green-50 border-green-200 text-green-800';
      case 'error':
        return 'bg-red-50 border-red-200 text-red-800';
      case 'warning':
        return 'bg-yellow-50 border-yellow-200 text-yellow-800';
      case 'info':
        return 'bg-blue-50 border-blue-200 text-blue-800';
      default:
        return 'bg-gray-50 border-gray-200 text-gray-800';
    }
  };

  const getIcon = () => {
    switch (type) {
      case 'success':
        return '✅';
      case 'error':
        return '❌';
      case 'warning':
        return '⚠️';
      case 'info':
        return 'ℹ️';
      default:
        return '📝';
    }
  };

  return (
    <div
      className={`max-w-sm w-full border rounded-lg shadow-lg p-4 ${getTypeStyles()} transition-all duration-300`}
    >
      <div className='flex items-start'>
        <div className='flex-shrink-0'>
          <span className='text-lg'>{getIcon()}</span>
        </div>
        <div className='ml-3 w-0 flex-1'>
          <p className='text-sm font-medium'>{title}</p>
          {message && <p className='mt-1 text-sm opacity-90'>{message}</p>}
        </div>
        <div className='ml-4 flex-shrink-0 flex'>
          <button
            onClick={() => onRemove(id)}
            className='inline-flex text-gray-400 hover:text-gray-600 focus:outline-none'
          >
            <span className='sr-only'>Fechar</span>
            <svg className='h-5 w-5' fill='currentColor' viewBox='0 0 20 20'>
              <path
                fillRule='evenodd'
                d='M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z'
                clipRule='evenodd'
              />
            </svg>
          </button>
        </div>
      </div>
    </div>
  );
}

export default function NotificationContainer({
  notifications: propNotifications,
  onRemove,
}: NotificationContainerProps) {
  const [notifications, setNotifications] = useState<Notification[]>(
    propNotifications || []
  );

  useEffect(() => {
    if (!propNotifications) {
      const unsubscribe = notificationManager.subscribe(setNotifications);
      return unsubscribe;
    }
  }, [propNotifications]);

  const handleRemove = (id: string) => {
    if (onRemove) {
      onRemove(id);
    } else {
      notificationManager.remove(id);
    }
  };

  if (notifications.length === 0) return null;

  return (
    <div className='fixed bottom-0 right-0 z-50 p-4 space-y-4 pointer-events-none'>
      <div className='flex flex-col space-y-4 pointer-events-auto'>
        {notifications.map((notification) => (
          <NotificationItem
            key={notification.id}
            notification={notification}
            onRemove={handleRemove}
          />
        ))}
      </div>
    </div>
  );
}
