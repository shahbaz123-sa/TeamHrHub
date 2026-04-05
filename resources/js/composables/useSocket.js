import { useSocket } from "@/plugins/socket";

export const useSocketOn = (eventName, callback) => {
  const socket = useSocket();
  if (socket) {
    socket.on(eventName, callback);

    // Return unsubscribe function for cleanup
    return () => socket.off(eventName, callback);
  }
};

export const useSocketEmit = (eventName, data) => {
  const socket = useSocket();
  if (socket) {
    socket.emit(eventName, data);
  }
};
