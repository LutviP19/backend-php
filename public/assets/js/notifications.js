import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js";
import {
  getMessaging,
  getToken,
  onMessage,
  isSupported,
} from "https://www.gstatic.com/firebasejs/10.12.2/firebase-messaging.js";

const firebaseConfig = {
  apiKey: "AIzaSyAwPuq6pS3iCVb2j6mKFgI_DjxEnSirGgg",
  authDomain: "simple-5a67b.firebaseapp.com",
  projectId: "simple-5a67b",
  storageBucket: "simple-5a67b.firebasestorage.app",
  messagingSenderId: "254254374469",
  appId: "1:254254374469:web:8e929086792e386bf4cd2c",
};
const app = initializeApp(firebaseConfig);

// Current saved Fcm Token
const fcmToken = document.getElementById("fcmToken-input").value;
const fcmTokenExpiry = document.getElementById("fcmTokenExpiry-input").value;
// Check expiry token
const now = new Date();
const expiryDate = new Date(fcmTokenExpiry); // Example: October 26, 2025, 10:00 AM

async function registerSW() {
  return await navigator.serviceWorker.register("/happyfew-sw-v3.js");
}

function delay(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function requestPermissionAndGetToken() {
  
  if (!(await isSupported())) {
    console.log("Messaging not supported");
    // tokenBox.classList.add("text-danger");
    // nextBtn.classList.remove('disabled');
    return;
  }

  const r = await Notification.requestPermission();
  
  // alert("Permission: " + r);
  console.log("Permission: " + r);

  const reg = await registerSW();
  const messaging = getMessaging(app);
  const t = await getToken(messaging, {
    vapidKey: "BKykTlZyhoU5dkGcDy4ESfDY5Xwyb36B9Nzz5MPJYSZMcrapLOJfV5saKliwTeLcEooa3t9Es5s6B0tYeWDG6UE",
    serviceWorkerRegistration: reg,
  });

  // console.log("fcmToken: " + fcmToken);
  // Check token is still valid, to set flag forceUpdate token
  const forceUpdate = (fcmToken == '' || t !== fcmToken || expiryDate < now);
  // console.log("forceUpdate: " + forceUpdate);
  
  await delay(1000); // Wait for 2 seconds
  // Update the token to backend
  if(forceUpdate) {
    await updateTokenToServer(t, forceUpdate);
  }
  // console.log('deviceToken', t);
}

// Save token
async function updateTokenToServer(token, forceUpdate) {
  const apiUrl = "/api/update-fcm-token/";
  const userId = document.getElementById("fcmUserId-input").value;
  const userType = document.getElementById("fcmUserType-input").value;

  axios.post(apiUrl + userId, {
    fcmToken: token,
    userId: userId,
    userType: userType,
    forceUpdate: forceUpdate.toString(), // force update token flag
  })
  .then(function (response) {
    //   console.log(response.data);
    var fcmToken = response.data.data.fcmToken;
    // console.log('fcmToken', fcmToken);
  })
  .catch(function (error) {
    // console.log(error);
    // console.log(error.response.data);
    const errors = error.response.data.errors;
    // console.log(errors);
  });
}

// Handler pesan saat tab aktif
(async () => {
  if (await isSupported()) {
    const messaging = getMessaging(app);
    onMessage(messaging, (payload) => {
      if (Notification.permission === "granted") {
        const n = payload.notification || {};
        new Notification(n.title || "Message", {
          body: n.body || "",
          icon: "/assets/icons/new-icon-192.png",
        });
      } else {
        console.log("Notification is blocked by user.");
        return;
      }
    });
  }
})();

// Call this function when appropriate, e.g., on a button click or after user login
(async () => {
  await requestPermissionAndGetToken();
})();