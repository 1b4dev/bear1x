# Bear1x
Bear1x is a lightweight Backend for Frontend (BFF) middleware designed for PHP backends to seamlessly create and access Bearer tokens as cookies. Acting as a proxy, Bear1x extracts tokens from cookies, forwards them to your backend, and delivers the response back to the frontend—all without requiring modifications to your existing backend.

Bear1x features:
- **Lightweight:** Minimal overhead for optimal performance.
- **Non-Invasive:** No need to edit or refactor your backend.
- **OOP PHP:** Built using modern Object-Oriented PHP principles.
- **cUrl-Free:** Eliminates cUrl-related issues for smoother operations.

## How It Works

Bear1x intercepts requests from the frontend.
Extracts the Bearer token from the cookie.
Proxies the token to your backend.
Returns the backend response to the frontend.

## Usage

Bear1x can be integrated into your project in two ways: **Direct Integration** or **Middleware Mode**. Choose the method that best suits your architecture.

---

### **1. Direct Integration**

This method involves placing Bear1x at the root of your API and adjusting the `BFF.php` file to match your backend configuration.

#### Steps:
1. **Place Bear1x at the Root**:  
   Move the Bear1x files to the root of your API directory (e.g., where your `index.php` or routing file is located).

2. **Configure `BFF.php`**:  
   Open `BFF.php` and adjust the necessary fields, such as:
   - **API Endpoint**: Set the root API endpoint for your backend.
   - **Token Handling**: Configure how Bearer tokens are extracted and validated.

3. **Update Routing**:  
   If your application uses index-based routing (e.g., `index.php`), add Bear1x's routing logic to handle incoming requests. For example:
   ```php
   // index.php
    else if (isset($uriSegments[0]) && $uriSegments[0] === 'bff') {
    array_shift($uriSegments);
        $bff = new BFFMiddleware();
        try {
            $bff->handleRequest($uriSegments);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal Server Error: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
    }
    ```
4. **Frontend Requests**:  
   Update your frontend to prefix API calls with `/bff/`. For example:
   - Original: `/api/login`  
   - Updated: `/bff/api/login`

   This ensures that all requests are routed through Bear1x.

### **2. Middleware Mode**

In this mode, Bear1x acts as a standalone middleware layer. This is ideal for developers who want to keep Bear1x separate from their backend.

#### Steps:
1. **Adjust `BFF.php` Configuration**:  
   Open `BFF.php` and set the `host` field in the constructor to point to your backend URL. For example:
   ```php
   public function __construct() {
       $this->host = 'https://your-backend-url.com';
   }
   ```
2. **Deploy Bear1x as Middleware**:  
   Place Bear1x in a separate directory or server, ensuring it can act as a proxy between your frontend and backend.

3. **Frontend Requests**:  
   Similar to Direct Integration, prefix your API calls with `/bff/`. For example:
   - Original: `/api/login`  
   - Updated: `/bff/api/login`

   All requests will now be routed through Bear1x, which will forward them to your backend.