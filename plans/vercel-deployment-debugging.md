# Vercel Deployment Debugging Plan

## Project Overview
- **Project Name**: Cloud Quiz PWA
- **Language**: PHP/HTML/JavaScript
- **Current Issues**: 404 errors for static files, MIME type mismatches
- **Deployment Target**: Vercel

## Current Project Structure Analysis
```
/
├── api/
│   └── ranking.php
├── data/
│   ├── aws.json
│   ├── gcp.json
│   └── generate_quiz_data.py
├── doc/
│   └── debug
├── img/
│   └── img.tree.json
├── quiz/
│   ├── app.js
│   ├── icon.png
│   ├── manifest.json
│   ├── quiz_data.json
│   └── quiz.php
└── .gitignore
```

## Root Cause Analysis

### Issue 1: 404 Errors for Static Files
**Root Cause**: Vercel's default routing doesn't serve static files from PHP directories
**Evidence**: Browser console shows 404 errors for app.js, style.css, manifest.json, favicon.ico

### Issue 2: MIME Type Mismatches
**Root Cause**: Vercel is serving files as text/plain instead of correct MIME types
**Evidence**: CSS/JS files being blocked due to MIME type errors

### Issue 3: PHP Deployment
**Root Cause**: Vercel doesn't natively support PHP
**Evidence**: Need for special runtime configuration

## Step-by-Step Debugging Plan

### Phase 1: Vercel Configuration

#### 1.1 Create vercel.json Configuration
```json
{
  "buildCommand": "echo 'No build required'",
  "outputDirectory": ".",
  "framework": null,
  "rewrites": [
    {
      "source": "/api/(.*)",
      "destination": "/api/$1"
    },
    {
      "source": "/quiz/(.*)",
      "destination": "/quiz/$1"
    },
    {
      "source": "/img/(.*)",
      "destination": "/img/$1"
    },
    {
      "source": "/data/(.*)",
      "destination": "/data/$1"
    },
    {
      "source": "/doc/(.*)",
      "destination": "/doc/$1"
    }
  ],
  "headers": [
    {
      "source": "/(.*)",
      "headers": [
        {
          "key": "Content-Type",
          "value": "text/html; charset=utf-8"
        },
        {
          "key": "X-Content-Type-Options",
          "value": "nosniff"
        },
        {
          "key": "X-Frame-Options",
          "value": "DENY"
        },
        {
          "key": "X-XSS-Protection",
          "value": "1; mode=block"
        }
      ]
    },
    {
      "source": "/quiz/(.*\\.(js|css|png|jpg|jpeg|gif|svg|ico|json|xml))",
      "headers": [
        {
          "key": "Cache-Control",
          "value": "public, max-age=31536000, immutable"
        }
      ]
    }
  ],
  "redirects": [
    {
      "source": "/",
      "destination": "/quiz/quiz.php",
      "statusCode": 308
    }
  ]
}
```

#### 1.2 Add PHP Runtime Configuration
Create `vercel-php.json`:
```json
{
  "runtime": "vercel-php@0.1.0"
}
```

### Phase 2: Static File Path Correction

#### 2.1 Fix quiz.php Static File References
```php
// Current (problematic)
<link rel="stylesheet" href="style.css">
<script src="app.js"></script>

// Fixed (absolute paths)
<link rel="stylesheet" href="/quiz/style.css">
<script src="/quiz/app.js"></script>
<link rel="manifest" href="/quiz/manifest.json">
<link rel="icon" href="/quiz/icon.png">
```

#### 2.2 Create CSS File
Create `/quiz/style.css`:
```css
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    background-color: #f0f2f5;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 2rem;
    min-height: 100vh;
}

.container {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    text-align: center;
    max-width: 600px;
    width: 100%;
}

/* Add other styles as needed */
```

### Phase 3: MIME Type Configuration

#### 3.1 Add MIME Type Headers
Update `vercel.json` headers section:
```json
{
  "headers": [
    {
      "source": "/quiz/(.*\\.css)",
      "headers": [
        {
          "key": "Content-Type",
          "value": "text/css; charset=utf-8"
        }
      ]
    },
    {
      "source": "/quiz/(.*\\.js)",
      "headers": [
        {
          "key": "Content-Type",
          "value": "application/javascript; charset=utf-8"
        }
      ]
    },
    {
      "source": "/quiz/(.*\\.json)",
      "headers": [
        {
          "key": "Content-Type",
          "value": "application/json; charset=utf-8"
        }
      ]
    },
    {
      "source": "/quiz/(.*\\.(png|jpg|jpeg|gif|svg|ico))",
      "headers": [
        {
          "key": "Content-Type",
          "value": "image/$1"
        }
      ]
    }
  ]
}
```

### Phase 4: Directory Structure Verification

#### 4.1 Verify Directory Structure
```
/quiz/
├── quiz.php          # Main PHP file
├── app.js           # JavaScript file
├── style.css        # CSS file (to be created)
├── manifest.json    # PWA manifest
├── icon.png         # Favicon
└── quiz_data.json   # Quiz data
```

#### 4.2 Create Missing Files
- Create `style.css` in quiz directory
- Verify `manifest.json` exists and is valid
- Verify `icon.png` exists

### Phase 5: Testing and Validation

#### 5.1 Local Testing
1. Test all file paths locally
2. Verify MIME types are correct
3. Test PHP functionality

#### 5.2 Vercel Deployment Testing
1. Deploy to Vercel
2. Check browser console for errors
3. Verify all static files load correctly
4. Test PHP endpoints

## Implementation Priority

### High Priority (Blockers)
1. Create `vercel.json` configuration
2. Fix static file paths in quiz.php
3. Create missing CSS file
4. Add MIME type headers

### Medium Priority (Important)
1. Add PHP runtime configuration
2. Verify all static assets exist
3. Test deployment

### Low Priority (Nice to have)
1. Add caching headers
2. Add security headers
3. Optimize performance

## Troubleshooting Guide

### Common Issues and Solutions

#### Issue: 404 Errors for Static Files
**Solution**: Check file paths in quiz.php and ensure files exist in correct locations

#### Issue: MIME Type Errors
**Solution**: Add specific Content-Type headers in vercel.json

#### Issue: PHP Not Working
**Solution**: Add PHP runtime configuration and verify PHP files are accessible

#### Issue: Directory Structure Problems
**Solution**: Ensure all files are in correct directories and paths are correct

## Success Criteria

### Functional Requirements
- [ ] All static files load without 404 errors
- [ ] All MIME types are correct
- [ ] PHP endpoints work correctly
- [ ] Quiz application functions properly

### Performance Requirements
- [ ] Page loads within 3 seconds
- [ ] All assets are cached properly
- [ ] No console errors

### Security Requirements
- [ ] Security headers are in place
- [ ] No mixed content warnings
- [ ] Proper CORS configuration

## Next Steps

1. Implement Phase 1: Create vercel.json configuration
2. Implement Phase 2: Fix static file paths
3. Implement Phase 3: Add MIME type headers
4. Implement Phase 4: Verify directory structure
5. Implement Phase 5: Test and validate

## Monitoring and Maintenance

- Monitor Vercel deployment logs
- Check browser console for errors
- Monitor performance metrics
- Update configuration as needed