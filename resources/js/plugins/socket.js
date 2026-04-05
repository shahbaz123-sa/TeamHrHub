import { io } from "socket.io-client";

let socket = null;

export const useSocket = () => {
  if (socket && socket.connected) {
    return socket;
  }

  const socketUrl = import.meta.env.VITE_SOCKET_URL || "https://hrm.dev.zareaportal.com";

  socket = io(socketUrl, {
    reconnection: true,
    reconnectionDelay: 1000,
    reconnectionDelayMax: 5000,
    reconnectionAttempts: 5,
    transports: ["websocket"],
  });

  socket.on("connect", () => {
    console.log("Socket connected:", socket.id);
  });

  socket.on("disconnect", () => {
    console.log("Socket disconnected");
  });

  socket.on("error", (error) => {
    console.error("Socket error:", error);
  });

  return socket;
};
