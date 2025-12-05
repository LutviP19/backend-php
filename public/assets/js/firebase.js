// Initialize Firebase
// import { axios } from "/assets/js/axios.min.js";
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
const allow_notification = document.getElementById("allow_notification");
const tokenBox = document.getElementById("tokenBox");
const setToken = (t) => (tokenBox.textContent = t);

async function registerSW() {
  return await navigator.serviceWorker.register("/backend-php-sw.js");
}

function delay(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

// FCM
async function requestPermissionAndGetToken() {
  if (!(await isSupported())) {
    setToken("Messaging not supported");
    tokenBox.classList.add("text-danger");
    return;
  }

  const reg = await registerSW();
  const messaging = getMessaging(app);
  const t = await getToken(messaging, {
    vapidKey:
      "BB6cYo9XK7YlqO4BjngaK6IlWgOnUAwQrl83cL1hax9yn1I-h6iFkBe-y30nlmgFe103VExFT5nMZOjj1tNR_fs",
    serviceWorkerRegistration: reg,
  });
  setToken("Token created successfully.");
  tokenBox.classList.add("text-success");
  // Save the token to backend
  await sendTokenToServer(t);
  // console.log('deviceToken', deviceToken);

  await delay(3000); // Wait for 3 seconds
  // Test push notification
  const resultTest = await sendToDevice(t);
  // console.log('resultTest: ', resultTest);
}

// Save token
async function sendTokenToServer(token) {
  // const apiUrl = regType == 'customer' ? '/api/v1/reg/customer/step2/' : '/api/v1/reg/driver/step2/';
  const apiUrl = "/api/save-fcm-token/";

  // setToken("Setup your token to system.");
  // tokenBox.classList.add('text-info');
  axios
    .post(apiUrl + regIdQs, {
      fcmToken: token,
      regId: regIdQs,
      regType: regType,
    })
    .then(function (response) {
      //   console.log(response.data);
      //   var fcmToken = response.data.data.fcmToken;
      //   console.log('fcmToken', fcmToken);
      //   setToken("Success setup token for your device.");
      //   tokenBox.classList.add('text-success');
    })
    .catch(function (error) {
      // console.log(error);
      // console.log(error.response.data);
      const errors = error.response.data.errors;
      //   console.log(errors);

      setToken("Failed setup token for your device.");
      tokenBox.classList.add("text-danger");
      return;
    });
}

// Test FCM Notification
async function sendToDevice(token) {
  const apiUrl = "/api/test-fcm-token/";

  // setToken("Test push notification to BackendPhp platform.");
  // tokenBox.classList.add('text-warning');
  axios
    .post(apiUrl + regIdQs, {
      fcmToken: token,
      title: "Hello from BackendPhp",
      body: "Notification from BackendPhp platform",
    })
    .then(function (response) {
      //   console.log(response.data);
      var result = response.data.data.result;
      //   console.log("result: ", result);

      setToken("Success send notification to your device.");
      tokenBox.classList.add("text-success");
      allow_notification.value = "active";
    })
    .catch(function (error) {
      //   console.log(error);
      //   console.log(error.response.data);
      //   const errors = error.response.data.errors;
      //   console.log(errors);

      setToken("Failed send notification to your device.");
      tokenBox.classList.add("text-danger");
      return;
    });
}

// Call this function when appropriate, e.g., on a button click or after user login
(async () => {
  await requestPermissionAndGetToken();
})();

// Handler pesan saat tab aktif
(async () => {
  if (await isSupported()) {
    const messaging = getMessaging(app);
    onMessage(messaging, (payload) => {
      if (Notification.permission === "granted") {
        const n = payload.notification || {};
        new Notification(n.title || "Message", {
          body: n.body || "",
          icon: "/assets/icons/icon-192.png",
        });
      }
    });
  }
})();
